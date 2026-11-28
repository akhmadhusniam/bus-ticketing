<?php

namespace Tests\Feature;

use App\Services\Booking\SeatLockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LogicException;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class SeatExpiryTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_expired_booking_releases_seats(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, expired: true);

        $this->artisan('bookings:release-expired')
            ->expectsOutput('Dilepas: 1 booking')
            ->assertExitCode(0);

        $booking->refresh();
        $fixture['seat']->refresh();

        $this->assertSame('expired', $booking->status);
        $this->assertSame('available', $fixture['seat']->status);
        $this->assertNull($fixture['seat']->locked_booking_id);
        $this->assertNull($fixture['seat']->locked_until);
    }

    public function test_release_expired_is_idempotent(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, expired: true);

        $this->artisan('bookings:release-expired')
            ->expectsOutput('Dilepas: 1 booking')
            ->assertExitCode(0);

        $this->artisan('bookings:release-expired')
            ->expectsOutput('Dilepas: 0 booking')
            ->assertExitCode(0);

        $booking->refresh();
        $fixture['seat']->refresh();

        $this->assertSame('expired', $booking->status);
        $this->assertSame('available', $fixture['seat']->status);
        $this->assertNull($fixture['seat']->locked_booking_id);
    }

    public function test_seat_can_be_reserved_after_release_and_old_booking_cannot_be_confirmed(): void
    {
        $fixture = $this->makeTrip();
        $oldBooking = $this->makeBookingWithLockedSeat($fixture, expired: true);

        $this->artisan('bookings:release-expired')
            ->expectsOutput('Dilepas: 1 booking')
            ->assertExitCode(0);

        $service = app(SeatLockService::class);

        $newBooking = $service->reserve($fixture['trip'], ['1A'], [
            ['name' => 'Penumpang Baru'],
        ], [
            'boarding_stop_id' => $fixture['boarding']->id,
            'dropping_stop_id' => $fixture['dropping']->id,
            'contact_name'     => 'Pemesan Baru',
            'contact_email'    => 'baru@example.com',
            'contact_phone'    => '081234567890',
        ]);

        $this->assertSame('pending', $newBooking->status);
        $this->assertSame(1, $newBooking->passengers()->count());

        $fixture['seat']->refresh();
        $this->assertSame('locked', $fixture['seat']->status);
        $this->assertSame($newBooking->id, $fixture['seat']->locked_booking_id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Booking tidak bisa dikonfirmasi.');

        $service->confirm($oldBooking);
    }

    public function test_confirm_after_expiry_is_rejected_even_with_paid_payment(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, expired: true);

        // Simulasi: scheduler sudah melepas kursi sebelum webhook pembayaran datang.
        $this->artisan('bookings:release-expired')
            ->expectsOutput('Dilepas: 1 booking')
            ->assertExitCode(0);

        $payment = $this->paidPayment($booking);
        $thrown = false;

        try {
            app(SeatLockService::class)->confirm($booking, $payment);
        } catch (LogicException $exception) {
            $thrown = true;
            $this->assertStringContainsString('tidak bisa dikonfirmasi', $exception->getMessage());
        }

        $this->assertTrue($thrown, 'confirm() seharusnya menolak booking yang sudah expired.');
        $this->assertSame('expired', $booking->fresh()->status);
        $this->assertSame('available', $fixture['seat']->fresh()->status);
    }

    public function test_confirm_paid_booking_books_seats_and_is_idempotent(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, expired: false);
        $payment = $this->paidPayment($booking);
        $service = app(SeatLockService::class);

        $service->confirm($booking, $payment);

        $booking->refresh();
        $fixture['seat']->refresh();

        $this->assertSame('paid', $booking->status);
        $this->assertSame('booked', $fixture['seat']->status);
        $this->assertNull($fixture['seat']->locked_until);
        $this->assertSame($booking->id, $fixture['seat']->locked_booking_id);

        // Webhook duplikat tidak boleh mengubah apa pun.
        $service->confirm($booking, $payment);

        $this->assertSame('paid', $booking->fresh()->status);
        $this->assertSame('booked', $fixture['seat']->fresh()->status);
    }
}
