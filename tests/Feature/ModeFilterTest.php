<?php

namespace Tests\Feature;

use App\Models\Bus;
use App\Models\BusLayout;
use App\Models\Route as RouteModel;
use App\Models\Trip;
use App\Models\TripSeat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class ModeFilterTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_mode_menyaring_armada_bus_dan_shuttle(): void
    {
        $fixture = $this->makeTrip();
        $baseBus = Bus::findOrFail($fixture['trip']->bus_id);
        $route = RouteModel::findOrFail($fixture['trip']->route_id);
        $date = $fixture['trip']->departure_at->copy()->setTimezone('Asia/Jakarta')->toDateString();

        $seatMap = [];
        foreach ([1, 2, 3] as $row) {
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $seatMap[] = [
                    'label' => $row.$letter,
                    'row'   => $row,
                    'col'   => ord($letter) - 64,
                    'deck'  => 1,
                    'type'  => 'regular',
                ];
            }
        }
        $seatMap[] = ['label' => '4A', 'row' => 4, 'col' => 1, 'deck' => 1, 'type' => 'regular'];
        $seatMap[] = ['label' => '4B', 'row' => 4, 'col' => 2, 'deck' => 1, 'type' => 'regular'];

        $shuttleLayout = BusLayout::create([
            'name'        => 'Shuttle Test 14 kursi',
            'decks'       => 1,
            'total_seats' => 14,
            'seat_map'    => ['rows' => 4, 'cols' => 4, 'aisle_after' => 2, 'seats' => $seatMap],
        ]);

        $shuttleBus = Bus::create([
            'operator_id'   => $baseBus->operator_id,
            'bus_layout_id' => $shuttleLayout->id,
            'plate_number'  => 'B 9999 SHT',
            'bus_type'      => 'seater',
            'service_mode'  => 'shuttle',
            'amenities'     => [],
            'is_active'     => true,
        ]);

        $shuttleTrip = Trip::create([
            'route_id'     => $route->id,
            'bus_id'       => $shuttleBus->id,
            'departure_at' => $fixture['trip']->departure_at->copy()->addMinutes(30),
            'arrival_at'   => $fixture['trip']->arrival_at->copy()->addMinutes(30),
            'base_price'   => 65000,
            'currency'     => 'IDR',
            'status'       => 'scheduled',
        ]);

        foreach ($seatMap as $seat) {
            TripSeat::create([
                'trip_id'    => $shuttleTrip->id,
                'seat_label' => $seat['label'],
                'deck'       => $seat['deck'],
                'price'      => 65000,
                'status'     => 'available',
            ]);
        }

        $params = [
            'origin'      => $route->origin_city_id,
            'destination' => $route->destination_city_id,
            'date'        => $date,
            'passengers'  => 1,
        ];

        $this->get(route('search', $params + ['mode' => 'bus']))
            ->assertOk()
            ->assertSee('Kapasitas 4 kursi', false)
            ->assertSee('Layout Test 4 kursi')
            ->assertDontSee('Shuttle Test 14 kursi');

        $this->get(route('search', $params + ['mode' => 'shuttle']))
            ->assertOk()
            ->assertSee('Kapasitas 14 kursi', false)
            ->assertSee('Shuttle Test 14 kursi')
            ->assertDontSee('Layout Test 4 kursi');
    }
}
