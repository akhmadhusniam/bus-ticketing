<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class TicketLookupController extends Controller
{
    public function show()
    {
        return view('tickets.lookup');
    }

    public function find(Request $request)
    {
        $data = $request->validate([
            'code'  => ['required', 'string', 'max:12'],
            'email' => ['required', 'email'],
        ], [
            'code.required'  => 'Kode booking wajib diisi.',
            'email.required' => 'Email pemesan wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ], [
            'code'  => 'Kode booking',
            'email' => 'Email pemesan',
        ]);

        $booking = $this->query()
            ->where('code', $this->normalizeCode($data['code']))
            ->where('contact_email', $data['email'])
            ->first();

        if (! $booking) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Kode booking dan email tidak cocok.']);
        }

        return $this->ticketView($booking);
    }

    /** E-tiket langsung setelah pembayaran; email opsional tapi diverifikasi bila diisi. */
    public function receipt(string $code)
    {
        $query = $this->query()->where('code', $this->normalizeCode($code));
        $email = request('email');

        if (is_string($email) && $email !== '') {
            $query->where('contact_email', $email);
        }

        return $this->ticketView($query->firstOrFail());
    }

    private function query()
    {
        return Booking::with([
            'trip.route.origin',
            'trip.route.destination',
            'trip.route.stops.busStop.city',
            'trip.bus.operator',
            'trip.bus.layout',
            'boardingStop.city',
            'droppingStop.city',
            'passengers.seat',
            'payments',
        ]);
    }

    private function ticketView(Booking $booking)
    {
        $payment = $booking->payments->firstWhere('status', 'paid')
            ?? $booking->payments->sortByDesc('id')->first();

        $trip = $booking->trip;
        $timezone = $trip->route->origin?->timezone ?: env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');

        return view('tickets.show', [
            'booking'   => $booking,
            'payment'   => $payment,
            'searchUrl' => route('search', [
                'origin'      => $trip->route->origin_city_id,
                'destination' => $trip->route->destination_city_id,
                'date'        => $trip->departure_at->copy()->setTimezone($timezone)->toDateString(),
                'passengers'  => max(1, $booking->passengers->count()),
                'mode'        => $trip->bus->service_mode,
            ]),
        ]);
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', $code) ?: '');
    }
}
