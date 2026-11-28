<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\TripSeat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_halaman_booking_menampilkan_pilihan_kursi_dan_form(): void
    {
        $fixture = $this->makeTrip();

        $this->get(route('booking.show', $fixture['trip']))
            ->assertOk()
            ->assertSee('1A')
            ->assertSee('Pilih kursi')
            ->assertSee('Langkah 3 dari 5', false)
            ->assertSee('aria-current="step"', false)
            ->assertSee('Keterangan status kursi', false)
            ->assertSee('Denah ilustratif', false)
            ->assertSee('Depan bus', false)
            ->assertSee('Kapasitas 4 kursi', false)
            ->assertSee('name="seats[]"', false)
            ->assertSee('0 dari 4 kursi dipilih', false)
            ->assertSee('Data penumpang')
            ->assertSee('Lanjut ke pembayaran');
    }

    public function test_pemesanan_membuat_booking_pending_dan_mengunci_kursi(): void
    {
        $fixture = $this->makeTrip();

        $response = $this->post(route('booking.store', $fixture['trip']), $this->payload($fixture));

        $booking = Booking::where('trip_id', $fixture['trip']->id)->firstOrFail();

        $response->assertRedirect(route('payment.show', $booking->code));

        $this->assertSame('pending', $booking->status);
        $this->assertSame(100000, (int) $booking->total_amount);
        $this->assertDatabaseHas('trip_seats', [
            'id'     => $fixture['seat']->id,
            'status' => 'locked',
        ]);
        $this->assertDatabaseHas('booking_passengers', [
            'booking_id' => $booking->id,
            'name'       => 'Penumpang Satu',
        ]);

        $this->followingRedirects()
            ->get(route('payment.show', $booking->code))
            ->assertOk()
            ->assertSee('Langkah 4 dari 5', false)
            ->assertSee('Kembali ke pilih kursi')
            ->assertSee('QRIS');
    }

    public function test_pembayaran_simulasi_menyelesaikan_booking_dan_menampilkan_etiket(): void
    {
        $fixture = $this->makeTrip();

        $this->post(route('booking.store', $fixture['trip']), $this->payload($fixture));

        $booking = Booking::where('trip_id', $fixture['trip']->id)->firstOrFail();

        $response = $this->post(route('payment.pay', $booking->code), ['method' => 'qris']);

        $response->assertRedirect(route('ticket.show', [
            'code'  => $booking->code,
            'email' => 'pemesan@example.com',
        ]));

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertDatabaseHas('trip_seats', [
            'id'     => $fixture['seat']->id,
            'status' => 'booked',
        ]);
        $this->assertSame('paid', Payment::where('booking_id', $booking->id)->firstOrFail()->status);

        $this->followingRedirects()
            ->get(route('ticket.show', ['code' => $booking->code]))
            ->assertOk()
            ->assertSee($booking->code)
            ->assertSee('Langkah 5 dari 5', false)
            ->assertSee('Penumpang Satu');
    }

    public function test_kursi_yang_sedang_dikunci_tidak_bisa_dipesan_kembali(): void
    {
        $fixture = $this->makeTrip();
        $payload = $this->payload($fixture);

        $this->post(route('booking.store', $fixture['trip']), $payload)->assertRedirect();

        $this->post(route('booking.store', $fixture['trip']), $payload)
            ->assertSessionHasErrors('seats');

        $this->assertSame(1, Booking::where('trip_id', $fixture['trip']->id)->count());
        $this->assertSame(1, TripSeat::where('trip_id', $fixture['trip']->id)->where('status', 'locked')->count());
    }

    /** @param array{boarding: mixed, dropping: mixed} $fixture */
    private function payload(array $fixture): array
    {
        return [
            'seats'            => ['1A'],
            'passenger'        => [[
                'name'   => 'Penumpang Satu',
                'age'    => 30,
                'gender' => 'female',
            ]],
            'boarding_stop_id' => $fixture['boarding']->id,
            'dropping_stop_id' => $fixture['dropping']->id,
            'contact_name'     => 'Pemesan Test',
            'contact_email'    => 'pemesan@example.com',
            'contact_phone'    => '081234567890',
        ];
    }
}
