<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class TripDetailTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_detail_menampilkan_titik_naik_dan_turun(): void
    {
        $fixture = $this->makeTrip();

        $this->get('/trip/'.$fixture['trip']->id)
            ->assertOk()
            ->assertSee($fixture['trip']->bus->operator->name)
            ->assertSee('Terminal Asal')
            ->assertSee('Terminal Tujuan')
            ->assertSee('Bisa naik')
            ->assertSee('Bisa turun');
    }

    public function test_detail_tidak_ditemukan_menghasilkan_404(): void
    {
        $this->get('/trip/999999')->assertNotFound();
    }
}
