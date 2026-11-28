<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatUnavailableException;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Booking\SeatLockService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use LogicException;

class PaymentController extends Controller
{
    /** @var array<string, array{label:string, detail:string, icon:string}> */
    private const METHODS = [
        'qris'   => [
            'label'  => 'QRIS',
            'detail' => 'Scan dengan aplikasi pembayaran favorit Anda.',
            'icon'   => 'QR',
        ],
        'va_bca' => [
            'label'  => 'Virtual Account BCA',
            'detail' => 'Transfer ke nomor virtual account yang dibuat otomatis.',
            'icon'   => 'VA',
        ],
        'gopay'  => [
            'label'  => 'GoPay',
            'detail' => 'Bayar lewat saldo GoPay Anda.',
            'icon'   => 'GP',
        ],
    ];

    public function show(string $code)
    {
        app(SeatLockService::class)->releaseExpired();

        $booking = $this->findBooking($code);

        if ($booking->status === 'paid') {
            return redirect()->route('ticket.show', $this->ticketParams($booking));
        }

        if ($booking->status !== 'pending' || $booking->expires_at?->isPast()) {
            return redirect()
                ->route('trip.detail', $booking->trip_id)
                ->with('error', 'Waktu pembayaran habis. Silakan pesan kursi kembali.');
        }

        $booking->load([
            'trip.route.origin',
            'trip.route.destination',
            'trip.bus.operator',
            'boardingStop.city',
            'droppingStop.city',
            'passengers.seat',
        ]);

        return view('tickets.payment', [
            'booking'     => $booking,
            'methods'     => self::METHODS,
            'secondsLeft' => max(0, (int) $booking->expires_at->diffInSeconds(now())),
            'backUrl'     => route('booking.show', $booking->trip_id),
        ]);
    }

    public function pay(Request $request, string $code)
    {
        app(SeatLockService::class)->releaseExpired();

        $booking = $this->findBooking($code);

        if ($booking->status === 'paid') {
            return redirect()->route('ticket.show', $this->ticketParams($booking));
        }

        if ($booking->status !== 'pending' || $booking->expires_at?->isPast()) {
            return redirect()
                ->route('trip.detail', $booking->trip_id)
                ->with('error', 'Waktu pembayaran habis. Silakan pesan kursi kembali.');
        }

        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(self::METHODS))],
        ], [
            'method.required' => 'Pilih metode pembayaran.',
            'method.in'       => 'Metode pembayaran tidak dikenal.',
        ]);

        $payment = Payment::create([
            'booking_id'   => $booking->id,
            'provider'     => 'demo',
            'provider_ref' => 'demo-'.strtolower($booking->code).'-'.strtolower($data['method']),
            'method'       => $data['method'],
            'amount'       => $booking->total_amount,
            'currency'     => $booking->currency,
            'status'       => 'paid',
            'paid_at'      => now(),
            'payload'      => ['simulated' => true],
        ]);

        try {
            app(SeatLockService::class)->confirm($booking, $payment);
        } catch (LogicException | SeatUnavailableException $exception) {
            $payment->update(['status' => 'failed', 'paid_at' => null]);

            return back()
                ->withInput()
                ->withErrors(['method' => 'Pembayaran tidak dapat diproses: '.$exception->getMessage()]);
        }

        return redirect()
            ->route('ticket.show', $this->ticketParams($booking))
            ->with('status', 'Pembayaran simulasi berhasil. E-tiket Anda siap.');
    }

    private function findBooking(string $code): Booking
    {
        $code = strtoupper(preg_replace('/\s+/', '', $code) ?: '');

        return Booking::where('code', $code)->firstOrFail();
    }

    /** @return array{code:string, email:string} */
    private function ticketParams(Booking $booking): array
    {
        return [
            'code'  => $booking->code,
            'email' => $booking->contact_email,
        ];
    }
}
