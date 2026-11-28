<?php

namespace Tests\Feature;

use App\Services\Booking\SeatLockService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use LogicException;
use Tests\Concerns\MakesBusFixtures;
use Tests\TestCase;

class SeatReservationTest extends TestCase
{
    use DatabaseTransactions;
    use MakesBusFixtures;

    public function test_duplicate_seat_labels_are_rejected(): void
    {
        $fixture = $this->makeTrip();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplikat');

        app(SeatLockService::class)->reserve(
            $fixture['trip'],
            ['1A', '1A'],
            [['name' => 'A'], ['name' => 'B']],
            $this->bookingData($fixture),
        );
    }

    public function test_passenger_count_must_match_seat_count(): void
    {
        $fixture = $this->makeTrip();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Jumlah penumpang');

        app(SeatLockService::class)->reserve(
            $fixture['trip'],
            ['1A'],
            [['name' => 'A'], ['name' => 'B']],
            $this->bookingData($fixture),
        );
    }

    public function test_stop_order_is_validated(): void
    {
        $fixture = $this->makeTrip();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Titik naik/turun');

        app(SeatLockService::class)->reserve(
            $fixture['trip'],
            ['1A'],
            [['name' => 'A']],
            $this->bookingData(
                $fixture,
                boardingId: $fixture['dropping']->id,
                droppingId: $fixture['boarding']->id,
            ),
        );
    }

    public function test_cancel_releases_seats_and_blocks_later_confirmation(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, expired: false);
        $service = app(SeatLockService::class);

        $service->cancel($booking);

        $this->assertSame('cancelled', $booking->fresh()->status);

        $fixture['seat']->refresh();
        $this->assertSame('available', $fixture['seat']->status);
        $this->assertNull($fixture['seat']->locked_booking_id);

        $thrown = false;

        try {
            $service->confirm($booking);
        } catch (LogicException $exception) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'Booking yang dibatalkan tidak boleh dikonfirmasi.');
    }

    public function test_refund_releases_seats_and_marks_payment_refunded(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, expired: false);
        $payment = $this->paidPayment($booking);
        $service = app(SeatLockService::class);

        $service->confirm($booking, $payment);
        $service->refund($booking);

        $this->assertSame('refunded', $booking->fresh()->status);
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('available', $fixture['seat']->fresh()->status);
    }

    public function test_confirm_rejects_amount_mismatch(): void
    {
        $fixture = $this->makeTrip();
        $booking = $this->makeBookingWithLockedSeat($fixture, expired: false);
        $payment = $this->paidPayment($booking);
        $payment->update(['amount' => 1]);

        $thrown = false;

        try {
            app(SeatLockService::class)->confirm($booking, $payment);
        } catch (LogicException $exception) {
            $thrown = true;
            $this->assertStringContainsString('Jumlah pembayaran', $exception->getMessage());
        }

        $this->assertTrue($thrown);
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('locked', $fixture['seat']->fresh()->status);
    }

    /**
     * @param array{trip: \App\Models\Trip, seat: \App\Models\TripSeat, boarding: \App\Models\BusStop, dropping: \App\Models\BusStop} $fixture
     */
    private function bookingData(array $fixture, ?int $boardingId = null, ?int $droppingId = null): array
    {
        return [
            'boarding_stop_id' => $boardingId ?? $fixture['boarding']->id,
            'dropping_stop_id' => $droppingId ?? $fixture['dropping']->id,
            'contact_name'     => 'Pemesan Test',
            'contact_email'    => 'pemesan@example.com',
            'contact_phone'    => '081234567890',
        ];
    }
}
