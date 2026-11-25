<?php

namespace App\Services\Booking;

use App\Exceptions\SeatUnavailableException;
use App\Models\Booking;
use App\Models\Trip;
use App\Models\TripSeat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeatLockService
{
    public const LOCK_MINUTES = 15;

    /**
     * Buat booking pending dan kunci kursi secara atomik.
     * lockForUpdate() membuat dua pembeli yang berebut kursi yang sama diproses bergantian.
     *
     * @param  string[] $seatLabels
     * @param  array<int, array{name:string, age?:int, gender?:string}> $passengers  urutannya sama dengan $seatLabels
     */
    public function reserve(Trip $trip, array $seatLabels, array $passengers, array $data): Booking
    {
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
                'code'            => strtoupper(Str::random(8)),
                'user_id'         => $data['user_id'] ?? null,
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

            foreach (array_values($seatLabels) as $i => $label) {
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

    /** Dipanggil dari webhook pembayaran setelah status paid terverifikasi. */
    public function confirm(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'paid']);
            TripSeat::where('locked_booking_id', $booking->id)
                ->update(['status' => 'booked', 'locked_until' => null]);
        });
    }

    /** Lepas kursi dari booking pending yang sudah lewat batas waktu. */
    public function releaseExpired(): int
    {
        return DB::transaction(function () {
            $ids = Booking::where('status', 'pending')
                ->where('expires_at', '<', now())
                ->pluck('id');

            TripSeat::whereIn('locked_booking_id', $ids)
                ->where('status', 'locked')
                ->update(['status' => 'available', 'locked_booking_id' => null, 'locked_until' => null]);

            Booking::whereIn('id', $ids)->update(['status' => 'expired']);

            return $ids->count();
        });
    }
}
