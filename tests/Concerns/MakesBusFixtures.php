<?php

namespace Tests\Concerns;

use App\Models\Booking;
use App\Models\Bus;
use App\Models\BusLayout;
use App\Models\BusStop;
use App\Models\City;
use App\Models\Country;
use App\Models\Operator;
use App\Models\Payment;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Services\Booking\SeatLockService;
use Illuminate\Support\Str;

trait MakesBusFixtures
{
    /**
     * @return array{trip: Trip, seats: \Illuminate\Support\Collection, seat: TripSeat, boarding: BusStop, dropping: BusStop}
     */
    private function makeTrip(): array
    {
        $country = Country::create([
            'code'          => $this->countryCode(),
            'name'          => 'Negara Test ' . Str::random(4),
            'currency_code' => 'IDR',
            'phone_prefix'  => '+62',
        ]);

        $origin = City::create([
            'country_id' => $country->id,
            'name'       => 'Asal ' . Str::random(4),
            'slug'       => 'asal-' . Str::lower(Str::random(8)),
            'timezone'   => 'Asia/Jakarta',
        ]);

        $destination = City::create([
            'country_id' => $country->id,
            'name'       => 'Tujuan ' . Str::random(4),
            'slug'       => 'tujuan-' . Str::lower(Str::random(8)),
            'timezone'   => 'Asia/Jakarta',
        ]);

        $boarding = BusStop::create([
            'city_id' => $origin->id,
            'name'    => 'Terminal Asal',
        ]);

        $dropping = BusStop::create([
            'city_id' => $destination->id,
            'name'    => 'Terminal Tujuan',
        ]);

        $layout = BusLayout::create([
            'name'        => 'Layout Test 4 kursi',
            'decks'       => 1,
            'total_seats' => 4,
            'seat_map'    => [
                'rows'        => 2,
                'cols'        => 2,
                'aisle_after' => 1,
                'seats'       => [
                    ['label' => '1A', 'row' => 1, 'col' => 1, 'deck' => 1, 'type' => 'regular'],
                    ['label' => '1B', 'row' => 1, 'col' => 2, 'deck' => 1, 'type' => 'regular'],
                    ['label' => '2A', 'row' => 2, 'col' => 1, 'deck' => 1, 'type' => 'regular'],
                    ['label' => '2B', 'row' => 2, 'col' => 2, 'deck' => 1, 'type' => 'regular'],
                ],
            ],
        ]);

        $operator = Operator::create([
            'name'       => 'Operator Test ' . Str::random(4),
            'slug'       => 'operator-' . Str::lower(Str::random(8)),
            'country_id' => $country->id,
            'rating'     => 4.80,
        ]);

        $bus = Bus::create([
            'operator_id'   => $operator->id,
            'bus_layout_id' => $layout->id,
            'plate_number'  => 'B 1234 TST',
            'bus_type'      => 'seater',
            'service_mode'  => 'bus',
            'amenities'     => [],
        ]);

        $route = Route::create([
            'operator_id'         => $operator->id,
            'origin_city_id'      => $origin->id,
            'destination_city_id' => $destination->id,
            'duration_minutes'    => 120,
        ]);

        RouteStop::create([
            'route_id'      => $route->id,
            'bus_stop_id'   => $boarding->id,
            'stop_order'    => 1,
            'offset_minutes'=> 0,
            'is_boarding'   => true,
            'is_dropping'   => false,
        ]);

        RouteStop::create([
            'route_id'      => $route->id,
            'bus_stop_id'   => $dropping->id,
            'stop_order'    => 2,
            'offset_minutes'=> 120,
            'is_boarding'   => false,
            'is_dropping'   => true,
        ]);

        $trip = Trip::create([
            'route_id'     => $route->id,
            'bus_id'       => $bus->id,
            'departure_at' => now()->addDay(),
            'arrival_at'   => now()->addDay()->addHours(2),
            'base_price'   => 100000,
            'currency'     => 'IDR',
            'status'       => 'scheduled',
        ]);

        $seats = collect(['1A', '1B', '2A', '2B'])->map(fn (string $label) => TripSeat::create([
            'trip_id'    => $trip->id,
            'seat_label' => $label,
            'deck'       => 1,
            'price'      => 100000,
            'status'     => 'available',
        ]));

        return compact('trip', 'seats', 'boarding', 'dropping') + ['seat' => $seats->first()];
    }

    /**
     * @param array{trip: Trip, seat: TripSeat, boarding: BusStop, dropping: BusStop} $fixture
     */
    private function makeBookingWithLockedSeat(array $fixture, bool $expired): Booking
    {
        $expiresAt = $expired
            ? now()->subMinute()
            : now()->addMinutes(SeatLockService::LOCK_MINUTES);

        $booking = Booking::create([
            'code'            => strtoupper(Str::random(8)),
            'trip_id'         => $fixture['trip']->id,
            'boarding_stop_id'=> $fixture['boarding']->id,
            'dropping_stop_id'=> $fixture['dropping']->id,
            'contact_name'    => 'Pemesan Test',
            'contact_email'   => 'pemesan@example.com',
            'contact_phone'   => '081234567890',
            'total_amount'    => 100000,
            'currency'        => 'IDR',
            'status'          => 'pending',
            'expires_at'      => $expiresAt,
        ]);

        $fixture['seat']->update([
            'status'            => 'locked',
            'locked_booking_id' => $booking->id,
            'locked_until'      => $expiresAt,
        ]);

        return $booking;
    }

    private function paidPayment(Booking $booking): Payment
    {
        return Payment::create([
            'booking_id'  => $booking->id,
            'provider'    => 'midtrans',
            'provider_ref'=> 'test-' . $booking->id,
            'method'      => 'qris',
            'amount'      => 100000,
            'currency'    => 'IDR',
            'status'      => 'paid',
            'paid_at'     => now(),
            'payload'     => [],
        ]);
    }

    /** Kode 2 huruf pertama yang belum dipakai, agar fixture tidak flaky. */
    private function countryCode(): string
    {
        foreach (range('A', 'Z') as $first) {
            foreach (range('A', 'Z') as $second) {
                $code = $first . $second;

                if (! Country::where('code', $code)->exists()) {
                    return $code;
                }
            }
        }

        throw new LogicException('Tidak ada kode negara uji yang tersedia.');
    }
}
