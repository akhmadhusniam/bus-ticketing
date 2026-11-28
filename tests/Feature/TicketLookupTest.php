<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class TicketLookupTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_pengguna_dapat_melihat_etiket_dengan_kode_dan_email_yang_benar(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, false);
        $this->paidPayment($booking);

        $booking->update(['status' => 'paid']);
        $fixture['seat']->update(['status' => 'booked']);

        $booking->passengers()->create([
            'trip_seat_id' => $fixture['seat']->id,
            'name'         => 'Penumpang Satu',
            'age'          => 30,
            'gender'       => 'male',
        ]);

        $this->post('/cek-tiket', [
            'code'  => $booking->code,
            'email' => 'pemesan@example.com',
        ])
            ->assertOk()
            ->assertSee($booking->code)
            ->assertSee('Penumpang Satu')
            ->assertSee('1A')
            ->assertSee('Lunas');
    }

    public function test_email_salah_menampilkan_error(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, false);

        $this->post('/cek-tiket', [
            'code'  => $booking->code,
            'email' => 'salah@example.com',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('code');
    }
}
