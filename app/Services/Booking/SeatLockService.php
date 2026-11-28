<?php

namespace App\Services\Booking;

use App\Exceptions\SeatUnavailableException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Trip;
use App\Models\TripSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class SeatLockService
{
    public const LOCK_MINUTES = 15;

    /**
     * Buat booking pending dan kunci kursi secara atomik.
     * lockForUpdate() membuat dua pembeli yang berebut kursi yang sama diproses bergantian.
     *
     * @param  string[] $seatLabels
     * @param  array<int, array{name:string, age?:int, gender?:string}> $passengers  urutannya sama dengan $seatLabels
     * @param  array{boarding_stop_id:int|string, dropping_stop_id:int|string, contact_name:string, contact_email:string, contact_phone:string} $data
     */
    public function reserve(Trip $trip, array $seatLabels, array $passengers, array $data): Booking
    {
        $seatLabels = array_values(array_map(
            fn ($label) => is_string($label) ? trim($label) : $label,
            $seatLabels,
        ));
        $passengers = array_values($passengers);

        $this->assertSeatInput($seatLabels, $passengers);
        $this->assertTripBookable($trip);
        $this->assertStopsValid($trip, $data);

        return DB::transaction(function () use ($trip, $seatLabels, $passengers, $data) {
            $seats = TripSeat::where('trip_id', $trip->id)
                ->whereIn('seat_label', $seatLabels)
                ->lockForUpdate()
                ->get();

            $unavailable = $seats->filter(fn (TripSeat $s) =>
                $s->status === 'booked'
                || $s->status === 'blocked'
                || ($s->status === 'locked' && $s->locked_until?->isFuture())
            )->pluck('seat_label')->all();

            $missing = array_diff($seatLabels, $seats->pluck('seat_label')->all());

            if ($unavailable || $missing) {
                throw new SeatUnavailableException(array_merge($unavailable, $missing));
            }

            $expiresAt = now()->addMinutes(self::LOCK_MINUTES);

            $booking = Booking::create([
                'code'            => $this->generateBookingCode(),
                'user_id'         => auth()->id(),
                'trip_id'         => $trip->id,
                'boarding_stop_id'=> $data['boarding_stop_id'],
                'dropping_stop_id'=> $data['dropping_stop_id'],
                'contact_name'    => $data['contact_name'],
                'contact_email'   => $data['contact_email'],
                'contact_phone'   => $data['contact_phone'],
                'total_amount'    => $seats->sum('price'),
                'currency'        => $trip->currency,
                'status'          => 'pending',
                'expires_at'      => $expiresAt,
            ]);

            foreach ($seatLabels as $i => $label) {
                $seat = $seats->firstWhere('seat_label', $label);
                $seat->update([
                    'status'            => 'locked',
                    'locked_booking_id' => $booking->id,
                    'locked_until'      => $expiresAt,
                ]);
                $booking->passengers()->create([
                    'trip_seat_id' => $seat->id,
                    'name'         => $passengers[$i]['name'],
                    'age'          => $passengers[$i]['age'] ?? null,
                    'gender'       => $passengers[$i]['gender'] ?? null,
                ]);
            }

            return $booking;
        });
    }

    /**
     * Dipanggil dari webhook pembayaran setelah status paid terverifikasi.
     * Idempoten: memanggil dua kali untuk booking yang sama tidak mengubah apa pun.
     */
    public function confirm(Booking $booking, ?Payment $payment = null): void
    {
        DB::transaction(function () use ($booking, $payment) {
            /** @var Booking $locked */
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $seats = TripSeat::where('locked_booking_id', $locked->id)
                ->lockForUpdate()
                ->get();

            $paidPayment = Payment::where('booking_id', $locked->id)
                ->where('status', 'paid')
                ->when($payment, fn ($query) => $query->whereKeyNot($payment->id))
                ->first();

            if ($locked->status === 'paid') {
                if ($seats->isNotEmpty()
                    && $seats->every(fn (TripSeat $seat) => $seat->status === 'booked')) {
                    return;
                }

                throw new LogicException('Booking sudah dibayar, tetapi status kursinya tidak konsisten.');
            }

            if ($locked->status !== 'pending' || $locked->expires_at?->isPast()) {
                throw new LogicException('Booking tidak bisa dikonfirmasi.');
            }

            $proof = $paidPayment ?? $payment;

            if ($proof === null || $proof->status !== 'paid') {
                throw new LogicException('Pembayaran belum terverifikasi.');
            }

            if ((int) $proof->booking_id !== (int) $locked->id) {
                throw new LogicException('Pembayaran tidak cocok dengan booking.');
            }

            if ((int) $proof->amount !== (int) $locked->total_amount
                || $proof->currency !== $locked->currency) {
                throw new LogicException('Jumlah pembayaran tidak cocok dengan total booking.');
            }

            if ($seats->isEmpty()
                || $seats->contains(fn (TripSeat $seat) => $seat->status !== 'locked')) {
                throw new LogicException('Kursi booking tidak lagi dalam kondisi terkunci.');
            }

            $locked->update(['status' => 'paid']);

            foreach ($seats as $seat) {
                $seat->update(['status' => 'booked', 'locked_until' => null]);
            }
        });
    }

    /** Batalkan booking dan lepas kursinya. */
    public function cancel(Booking $booking): void
    {
        $this->changeTo($booking, 'cancelled');
    }

    /** Tandai booking sebagai refunded, lepas kursinya, dan tandai pembayarannya. */
    public function refund(Booking $booking): void
    {
        $this->changeTo($booking, 'refunded');
    }

    /** Lepas kursi dari booking pending yang sudah lewat batas waktu. */
    public function releaseExpired(): int
    {
        return DB::transaction(function () {
            $bookings = Booking::where('status', 'pending')
                ->where('expires_at', '<', now())
                ->lockForUpdate()
                ->get();

            if ($bookings->isEmpty()) {
                return 0;
            }

            $ids = $bookings->pluck('id');

            TripSeat::whereIn('locked_booking_id', $ids)
                ->where('status', 'locked')
                ->update(['status' => 'available', 'locked_booking_id' => null, 'locked_until' => null]);

            Booking::whereIn('id', $ids)->update(['status' => 'expired']);

            return $bookings->count();
        });
    }

    private function changeTo(Booking $booking, string $status): void
    {
        DB::transaction(function () use ($booking, $status) {
            /** @var Booking $locked */
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['cancelled', 'refunded'], true)) {
                return;
            }

            if (! in_array($locked->status, ['pending', 'paid', 'expired'], true)) {
                throw new LogicException('Booking tidak bisa dibatalkan.');
            }

            $seats = TripSeat::where('locked_booking_id', $locked->id)
                ->whereIn('status', ['locked', 'booked'])
                ->lockForUpdate()
                ->get();

            foreach ($seats as $seat) {
                $seat->update([
                    'status'            => 'available',
                    'locked_booking_id' => null,
                    'locked_until'      => null,
                ]);
            }

            $locked->update(['status' => $status]);

            if ($status === 'refunded') {
                Payment::where('booking_id', $locked->id)
                    ->where('status', 'paid')
                    ->update(['status' => 'refunded']);
            }
        });
    }

    private function generateBookingCode(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = strtoupper(Str::random(8));

            if (! Booking::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new LogicException('Gagal membuat kode booking yang unik.');
    }

    private function assertSeatInput(array $seatLabels, array $passengers): void
    {
        if ($seatLabels === []) {
            throw new InvalidArgumentException('Minimal satu kursi harus dipilih.');
        }

        foreach ($seatLabels as $label) {
            if (! is_string($label) || $label === '') {
                throw new InvalidArgumentException('Label kursi tidak valid.');
            }
        }

        if (count($seatLabels) !== count(array_unique($seatLabels))) {
            throw new InvalidArgumentException('Terdapat label kursi yang duplikat.');
        }

        if (count($seatLabels) !== count($passengers)) {
            throw new InvalidArgumentException('Jumlah penumpang harus sama dengan jumlah kursi.');
        }

        foreach ($passengers as $i => $passenger) {
            if (! is_array($passenger) || empty($passenger['name'])) {
                throw new InvalidArgumentException('Nama penumpang ke-' . ($i + 1) . ' wajib diisi.');
            }
        }
    }

    private function assertTripBookable(Trip $trip): void
    {
        if ($trip->status !== 'scheduled') {
            throw new LogicException('Perjalanan ini tidak tersedia untuk dipesan.');
        }

        if ($trip->departure_at?->isPast()) {
            throw new LogicException('Perjalanan ini sudah berangkat.');
        }
    }

    private function assertStopsValid(Trip $trip, array $data): void
    {
        if (! isset($data['boarding_stop_id'], $data['dropping_stop_id'])) {
            throw new InvalidArgumentException('Titik naik dan titik turun wajib diisi.');
        }

        $boardingId = (int) $data['boarding_stop_id'];
        $droppingId = (int) $data['dropping_stop_id'];

        $stops = $trip->route->stops()
            ->get(['id', 'bus_stop_id', 'stop_order', 'is_boarding', 'is_dropping']);

        $boardingOrders = $stops
            ->where('bus_stop_id', $boardingId)
            ->where('is_boarding', true)
            ->pluck('stop_order');

        $droppingOrders = $stops
            ->where('bus_stop_id', $droppingId)
            ->where('is_dropping', true)
            ->pluck('stop_order');

        $valid = $boardingOrders->contains(
            fn ($order) => $droppingOrders->contains(fn ($dropOrder) => $order < $dropOrder)
        );

        if (! $valid) {
            throw new InvalidArgumentException('Titik naik/turun tidak valid untuk rute ini.');
        }
    }
}
