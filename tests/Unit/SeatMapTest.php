<?php

namespace Tests\Unit;

use App\Support\SeatMap;
use PHPUnit\Framework\TestCase;

class SeatMapTest extends TestCase
{
    public function test_layout_seats_are_grouped_by_deck_and_row_with_aisle_and_status(): void
    {
        $layout = [
            'decks'       => 2,
            'total_seats' => 4,
            'seat_map'    => [
                'cols'  => 2,
                'seats' => [
                    ['label' => '1A', 'row' => 1, 'col' => 1, 'deck' => 1, 'type' => 'regular'],
                    ['label' => '1B', 'row' => 1, 'col' => 2, 'deck' => 1, 'type' => 'regular'],
                    ['label' => '2A', 'row' => 2, 'col' => 1, 'deck' => 2, 'type' => 'sleeper'],
                    ['label' => '2B', 'row' => 2, 'col' => 2, 'deck' => 2, 'type' => 'sleeper'],
                ],
            ],
        ];

        $seats = collect([
            (object) ['seat_label' => '1A', 'status' => 'available', 'price' => 100000, 'deck' => 1],
            (object) ['seat_label' => '1B', 'status' => 'booked', 'price' => 100000, 'deck' => 1],
            (object) ['seat_label' => '2A', 'status' => 'available', 'price' => 120000, 'deck' => 2],
            (object) ['seat_label' => '2B', 'status' => 'locked', 'price' => 120000, 'deck' => 2],
        ]);

        $map = SeatMap::from($layout, $seats);

        $this->assertSame(4, $map->capacity());
        $this->assertSame(4, $map->seatCount());
        $this->assertSame(2, $map->columns());
        $this->assertSame(1, $map->aisleAfter());

        $decks = $map->decks();

        $this->assertCount(2, $decks);
        $this->assertSame('Dek bawah', $decks[0]['label']);
        $this->assertSame('Dek atas', $decks[1]['label']);
        $this->assertSame(['1A', '1B'], array_column($decks[0]['rows'][0]['seats'], 'label'));
        $this->assertSame(['2A', '2B'], array_column($decks[1]['rows'][0]['seats'], 'label'));

        $first = $decks[0]['rows'][0]['seats'][0];
        $this->assertTrue($first['selectable']);
        $this->assertSame('Tersedia', $first['state_label']);
        $this->assertSame("\u{25CB}", $first['symbol']);

        $taken = $decks[0]['rows'][0]['seats'][1];
        $this->assertFalse($taken['selectable']);
        $this->assertSame('Sudah dibooked', $taken['state_label']);
        $this->assertSame("\u{00D7}", $taken['symbol']);

        $this->assertSame(['min' => 100000, 'max' => 120000], $map->priceRange());
    }

    public function test_trip_seats_are_used_when_layout_has_no_seat_map(): void
    {
        $layout = ['decks' => 1, 'total_seats' => 2, 'seat_map' => ['cols' => 1]];

        $seats = collect([
            (object) ['seat_label' => '1A', 'status' => 'available', 'price' => 90000, 'deck' => 1],
            (object) ['seat_label' => '2A', 'status' => 'available', 'price' => 90000, 'deck' => 1],
        ]);

        $map = SeatMap::from($layout, $seats);
        $decks = $map->decks();

        $this->assertSame(2, $map->capacity());
        $this->assertSame(2, $map->seatCount());
        $this->assertSame(1, $map->columns());
        $this->assertSame(0, $map->aisleAfter());
        $this->assertCount(1, $decks);
        $this->assertCount(2, $decks[0]['rows']);
        $this->assertSame('1A', $decks[0]['rows'][0]['seats'][0]['label']);
        $this->assertSame('2A', $decks[0]['rows'][1]['seats'][0]['label']);
        $this->assertSame(['min' => 90000, 'max' => 90000], $map->priceRange());
    }

    public function test_shuttle_layout_has_fourteen_seats_and_a_short_last_row(): void
    {
        $seats = [];
        foreach ([1, 2, 3] as $row) {
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                $seats[] = ['label' => $row.$letter, 'row' => $row, 'col' => ord($letter) - 64, 'deck' => 1, 'type' => 'regular'];
            }
        }
        $seats[] = ['label' => '4A', 'row' => 4, 'col' => 1, 'deck' => 1, 'type' => 'regular'];
        $seats[] = ['label' => '4B', 'row' => 4, 'col' => 2, 'deck' => 1, 'type' => 'regular'];

        $map = SeatMap::from(
            ['decks' => 1, 'total_seats' => 14, 'seat_map' => ['cols' => 4, 'aisle_after' => 2, 'seats' => $seats]],
            collect(),
        );

        $this->assertSame(14, $map->capacity());
        $this->assertSame(14, $map->seatCount());
        $this->assertSame(2, $map->aisleAfter());
        $this->assertCount(4, $map->decks()[0]['rows']);
        $this->assertCount(2, $map->decks()[0]['rows'][3]['seats']);
    }

    public function test_configured_aisle_position_is_respected(): void
    {
        $layout = ['decks' => 1, 'seat_map' => ['cols' => 3, 'aisle_after' => 2]];

        $this->assertSame(2, SeatMap::from($layout, collect())->aisleAfter());
    }
}
