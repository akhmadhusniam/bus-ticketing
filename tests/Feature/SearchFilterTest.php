<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class SearchFilterTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_filter_rating_tinggi_menyaring_operator(): void
    {
        $fixture = $this->makeTrip();

        $this->get($this->searchUrl($fixture, ['min_rating' => '4.90']))
            ->assertOk()
            ->assertSee('Belum ada perjalanan')
            ->assertDontSee('Lihat detail');

        $this->get($this->searchUrl($fixture, ['min_rating' => '4.50']))
            ->assertOk()
            ->assertSee('Lihat detail')
            ->assertDontSee('Belum ada perjalanan');
    }

    public function test_filter_waktu_berangkat_dan_waktu_tiba(): void
    {
        $fixture = $this->makeTrip();
        $trip = $fixture['trip'];

        $depart = $trip->departure_at->copy()->setTimezone('Asia/Jakarta');
        $arrive = $trip->arrival_at->copy()->setTimezone('Asia/Jakarta');

        $this->get($this->searchUrl($fixture, [
            'departure_from' => $depart->copy()->subMinutes(5)->format('H:i'),
            'departure_to'   => $depart->copy()->addMinutes(5)->format('H:i'),
            'arrival_from'   => $arrive->copy()->subMinutes(5)->format('H:i'),
            'arrival_to'     => $arrive->copy()->addMinutes(5)->format('H:i'),
        ]))
            ->assertOk()
            ->assertSee('Lihat detail')
            ->assertDontSee('Belum ada perjalanan');

        $this->get($this->searchUrl($fixture, [
            'departure_from' => $depart->copy()->addHour()->format('H:i'),
            'departure_to'   => $depart->copy()->addHour()->addMinute()->format('H:i'),
        ]))
            ->assertOk()
            ->assertSee('Belum ada perjalanan')
            ->assertDontSee('Lihat detail');
    }

    public function test_filter_titik_penurunan(): void
    {
        $fixture = $this->makeTrip();

        $this->get($this->searchUrl($fixture, [
            'dropping_stop' => $fixture['dropping']->id,
        ]))
            ->assertOk()
            ->assertSee('Lihat detail')
            ->assertDontSee('Belum ada perjalanan');

        $this->get($this->searchUrl($fixture, [
            'dropping_stop' => $fixture['boarding']->id,
        ]))
            ->assertOk()
            ->assertSee('Belum ada perjalanan')
            ->assertDontSee('Lihat detail');
    }

    public function test_filter_fasilitas_multiple(): void
    {
        $fixture = $this->makeTrip();
        $fixture['trip']->bus->update(['amenities' => ['wifi', 'ac']]);

        $this->get($this->searchUrl($fixture, ['amenities' => ['wifi', 'ac']]))
            ->assertOk()
            ->assertSee('Lihat detail')
            ->assertDontSee('Belum ada perjalanan');

        $this->get($this->searchUrl($fixture, ['amenities' => ['wifi', 'charging']]))
            ->assertOk()
            ->assertSee('Belum ada perjalanan')
            ->assertDontSee('Lihat detail');
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function searchUrl(array $fixture, array $filters): string
    {
        $trip = $fixture['trip'];
        $date = $trip->departure_at->copy()->setTimezone('Asia/Jakarta')->toDateString();

        return '/cari?'.http_build_query(array_merge([
            'origin'      => $trip->route->origin_city_id,
            'destination' => $trip->route->destination_city_id,
            'date'        => $date,
            'passengers'  => 1,
        ], $filters));
    }
}
