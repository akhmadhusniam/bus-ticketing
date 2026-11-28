<?php

namespace App\Support;

use Illuminate\Support\Collection;

class SeatMap
{
    private Collection $seatsByLabel;

    public function __construct(private mixed $layout, private Collection $seats)
    {
        $this->seatsByLabel = $seats->keyBy('seat_label');
    }

    public static function from(mixed $layout, mixed $seats): self
    {
        return new self(
            $layout,
            $seats instanceof Collection ? $seats : collect($seats),
        );
    }

    public function capacity(): int
    {
        return max(0, (int) data_get($this->layout, 'total_seats', 0));
    }

    public function seatCount(): int
    {
        return $this->meta()->count();
    }

    public function columns(): int
    {
        return max(1, (int) data_get($this->layout, 'seat_map.cols', 2));
    }

    public function aisleAfter(): int
    {
        $columns = $this->columns();

        if ($columns < 2) {
            return 0;
        }

        $configured = (int) data_get($this->layout, 'seat_map.aisle_after', 0);

        if ($configured > 0 && $configured < $columns) {
            return $configured;
        }

        return (int) ceil($columns / 2);
    }

    /**
     * @return array<int, array{
     *     number: int,
     *     label: string,
     *     rows: array<int, array{number: int, seats: array<int, array<string, mixed>>}>
     * }>
     */
    public function decks(): array
    {
        return $this->decorated()
            ->groupBy('deck')
            ->sortKeys(SORT_NATURAL)
            ->map(function (Collection $seats, $deck) {
                return [
                    'number' => (int) $deck,
                    'label'  => $this->deckLabel((int) $deck),
                    'rows'   => $seats
                        ->groupBy('row')
                        ->sortKeys(SORT_NATURAL)
                        ->map(fn (Collection $row) => [
                            'number' => (int) $row->first()['row'],
                            'seats'  => $row->sortBy('col')->values()->all(),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{min: int, max: int}|null */
    public function priceRange(): ?array
    {
        $prices = $this->decorated()
            ->where('selectable', true)
            ->pluck('price')
            ->filter(fn ($price) => $price > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($prices === []) {
            return null;
        }

        return [
            'min' => (int) $prices[0],
            'max' => (int) $prices[count($prices) - 1],
        ];
    }

    private function decorated(): Collection
    {
        return $this->meta()->map(function (array $item) {
            $seat = $this->seatsByLabel->get($item['label']);
            $status = (string) ($seat?->status ?? 'blocked');

            return array_merge($item, [
                'seat'        => $seat,
                'status'      => $status,
                'selectable'  => $status === 'available',
                'state_label' => $this->stateLabel($status),
                'symbol'      => $status === 'available' ? '○' : '×',
                'price'       => (int) ($seat?->price ?? 0),
            ]);
        });
    }

    private function meta(): Collection
    {
        $layoutSeats = collect((array) data_get($this->layout, 'seat_map.seats', []));

        if ($layoutSeats->isNotEmpty()) {
            return $layoutSeats->map(fn ($seat) => [
                'label' => (string) ($seat['label'] ?? ''),
                'row'   => (int) ($seat['row'] ?? 1),
                'col'   => (int) ($seat['col'] ?? 1),
                'deck'  => (int) ($seat['deck'] ?? 1),
                'type'  => (string) ($seat['type'] ?? 'regular'),
            ]);
        }

        $columns = $this->columns();

        return $this->seats->values()->map(fn ($seat, $index) => [
            'label' => (string) $seat->seat_label,
            'row'   => intdiv($index, $columns) + 1,
            'col'   => ($index % $columns) + 1,
            'deck'  => (int) $seat->deck,
            'type'  => 'regular',
        ]);
    }

    private function deckLabel(int $deck): string
    {
        $labels = (array) data_get($this->layout, 'seat_map.deck_labels', []);

        if (isset($labels[$deck]) && is_string($labels[$deck]) && $labels[$deck] !== '') {
            return $labels[$deck];
        }

        $deckCount = max(1, (int) data_get($this->layout, 'decks', 1));

        if ($deckCount > 1 && $deck <= 2) {
            return $deck === 1 ? 'Dek bawah' : 'Dek atas';
        }

        return 'Dek '.$deck;
    }

    private function stateLabel(string $status): string
    {
        return match ($status) {
            'available' => 'Tersedia',
            'locked'    => 'Sedang dikunci',
            'booked'    => 'Sudah dibooked',
            default     => 'Tidak tersedia',
        };
    }
}
