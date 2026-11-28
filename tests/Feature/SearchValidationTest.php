<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

class SearchValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_follows_origin_city_timezone(): void
    {
        // 20:00 UTC = 03:00 WIB keesokan harinya.
        Carbon::setTestNow(Carbon::parse('2026-11-25 20:00:00', 'UTC'));

        $this->get('/cari?origin=1&destination=2&date=2026-11-26&passengers=1')
            ->assertOk();
    }

    public function test_previous_calendar_day_in_origin_timezone_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 20:00:00', 'UTC'));

        $response = $this->get('/cari?origin=1&destination=2&date=2026-11-25&passengers=1');

        $response->assertRedirect();
        $response->assertSessionHasErrors('date');
    }

    public function test_home_default_date_uses_display_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 20:00:00', 'UTC'));

        $this->get('/')
            ->assertOk()
            ->assertSee('min="2026-11-26"', false);
    }

    public function test_return_date_cannot_be_before_departure_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 20:00:00', 'UTC'));

        $response = $this->get('/cari?origin=1&destination=2&date=2026-11-26&return_date=2026-11-25&passengers=1');

        $response->assertRedirect();
        $response->assertSessionHasErrors('return_date');
    }

    public function test_return_leg_search_is_valid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 20:00:00', 'UTC'));

        $this->get('/cari?origin=1&destination=2&date=2026-11-26&return_date=2026-11-27&passengers=1')
            ->assertOk()
            ->assertSee('Cari tiket pulang');

        $this->get('/cari?origin=2&destination=1&date=2026-11-27&passengers=1&mode=bus')
            ->assertOk();
    }
}
