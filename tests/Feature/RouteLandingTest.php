<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class RouteLandingTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_halaman_rute_memiliki_seo_dan_jadwal(): void
    {
        $fixture = $this->makeTrip();
        $origin = $fixture['trip']->route->origin;
        $destination = $fixture['trip']->route->destination;

        $this->get('/rute/'.$origin->slug.'/'.$destination->slug)
            ->assertOk()
            ->assertSee('Bus '.$origin->name.' ke '.$destination->name, false)
            ->assertSee('application/ld+json', false)
            ->assertSee('FAQPage', false)
            ->assertSee($fixture['trip']->bus->operator->name);
    }

    public function test_rute_yang_tidak_ada_menghasilkan_404(): void
    {
        $this->get('/rute/kota-tidak-ada-tujuan-tidak-ada')->assertNotFound();
    }

    public function test_sitemap_memuat_rute(): void
    {
        $fixture = $this->makeTrip();
        $origin = $fixture['trip']->route->origin;
        $destination = $fixture['trip']->route->destination;

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
            ->assertSee('/rute/'.$origin->slug.'/'.$destination->slug, false);
    }
}
