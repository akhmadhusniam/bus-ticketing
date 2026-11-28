<?php

namespace App\Http\Controllers;

use App\Exceptions\SeatUnavailableException;
use App\Models\Trip;
use App\Services\Booking\SeatLockService;
use App\Support\SeatMap;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use LogicException;

class BookingController extends Controller
{
    private const MAX_SEATS = 8;

    public function show(Request $request, Trip $trip)
    {
        app(SeatLockService::class)->releaseExpired();

        if ($trip->status !== 'scheduled' || $trip->departure_at?->isPast()) {
            return redirect()
                ->route('trip.detail', $trip)
                ->with('error', 'Perjalanan ini sudah tidak tersedia untuk dipesan.');
        }

        $trip->load([
            'route.origin',
            'route.destination',
            'route.stops.busStop.city',
            'bus.operator',
            'bus.layout',
            'seats',
        ]);

        $stops = $trip->route->stops;
        $available = $trip->seats->where('status', 'available');
        $seatCount = $available->count();
        $wanted = min(
            max(1, (int) $request->integer('passengers', 1)),
            max(1, $seatCount),
            self::MAX_SEATS,
        );

        $seatMap = SeatMap::from($trip->bus->layout, $trip->seats);

        return view('trips.booking', [
            'trip'            => $trip,
            'boardingStops'   => $stops->where('is_boarding', true)->values(),
            'droppingStops'   => $stops->where('is_dropping', true)->values(),
            'seatMap'         => $seatMap,
            'seatColumns'     => $seatMap->columns(),
            'aisleAfter'      => $seatMap->aisleAfter(),
            'priceRange'      => $seatMap->priceRange(),
            'capacity'        => $seatMap->capacity(),
            'layoutSeatCount' => $seatMap->seatCount(),
            'seatCount'       => $seatCount,
            'passengerCount'  => $wanted,
            'estimate'        => $available->sortBy('price')->take($wanted)->sum('price'),
            'backUrl'         => $this->backUrl($trip, $request),
        ]);
    }

    public function store(Request $request, Trip $trip)
    {
        app(SeatLockService::class)->releaseExpired();

        $trip->load('route.stops');

        $boardingIds = $trip->route->stops
            ->where('is_boarding', true)
            ->pluck('bus_stop_id')
            ->all();
        $droppingIds = $trip->route->stops
            ->where('is_dropping', true)
            ->pluck('bus_stop_id')
            ->all();

        $data = $request->validate([
            'seats'                 => ['required', 'array', 'min:1', 'max:'.self::MAX_SEATS],
            'seats.*'               => [
                'required',
                'string',
                Rule::exists('trip_seats', 'seat_label')->where('trip_id', $trip->id),
            ],
            'passenger'             => ['required', 'array', 'min:1'],
            'passenger.*.name'      => ['required', 'string', 'max:120'],
            'passenger.*.age'       => ['nullable', 'integer', 'min:1', 'max:120'],
            'passenger.*.gender'    => ['nullable', Rule::in(['male', 'female'])],
            'boarding_stop_id'      => ['required', 'integer', Rule::in($boardingIds)],
            'dropping_stop_id'      => ['required', 'integer', Rule::in($droppingIds)],
            'contact_name'          => ['required', 'string', 'max:120'],
            'contact_email'         => ['required', 'email', 'max:150'],
            'contact_phone'         => ['required', 'string', 'max:30'],
        ], [
            'seats.required'              => 'Pilih minimal satu kursi.',
            'seats.array'                 => 'Pilihan kursi tidak valid.',
            'seats.max'                   => 'Maksimal '.self::MAX_SEATS.' kursi per pesanan.',
            'seats.*.exists'              => 'Kursi yang dipilih tidak tersedia.',
            'passenger.required'          => 'Data penumpang wajib diisi.',
            'passenger.*.name.required'   => 'Nama penumpang wajib diisi.',
            'passenger.*.age.integer'     => 'Umur harus berupa angka.',
            'boarding_stop_id.required'   => 'Titik naik wajib dipilih.',
            'boarding_stop_id.in'         => 'Titik naik tidak valid untuk rute ini.',
            'dropping_stop_id.required'   => 'Titik turun wajib dipilih.',
            'dropping_stop_id.in'         => 'Titik turun tidak valid untuk rute ini.',
            'contact_name.required'       => 'Nama pemesan wajib diisi.',
            'contact_email.required'      => 'Email pemesan wajib diisi.',
            'contact_email.email'         => 'Format email tidak valid.',
            'contact_phone.required'      => 'Nomor telepon wajib diisi.',
        ], [
            'seats'            => 'kursi',
            'passenger'        => 'penumpang',
            'boarding_stop_id' => 'titik naik',
            'dropping_stop_id' => 'titik turun',
            'contact_name'     => 'nama pemesan',
            'contact_email'    => 'email pemesan',
            'contact_phone'    => 'nomor telepon',
        ]);

        $seatLabels = array_values(array_map(
            fn ($label) => is_string($label) ? trim($label) : $label,
            $data['seats'],
        ));
        $passengers = array_values($data['passenger']);

        if (count($seatLabels) !== count($passengers)) {
            return back()
                ->withInput()
                ->withErrors(['seats' => 'Jumlah penumpang harus sama dengan jumlah kursi yang dipilih.']);
        }

        try {
            $booking = app(SeatLockService::class)->reserve($trip, $seatLabels, $passengers, [
                'boarding_stop_id' => $data['boarding_stop_id'],
                'dropping_stop_id' => $data['dropping_stop_id'],
                'contact_name'     => $data['contact_name'],
                'contact_email'    => $data['contact_email'],
                'contact_phone'    => $data['contact_phone'],
            ]);
        } catch (SeatUnavailableException $exception) {
            return back()
                ->withInput()
                ->withErrors(['seats' => 'Kursi '.implode(', ', $exception->seatLabels).' baru saja diambil. Silakan pilih kursi lain.']);
        } catch (InvalidArgumentException | LogicException $exception) {
            return back()
                ->withInput()
                ->withErrors(['seats' => $exception->getMessage()]);
        }

        return redirect()
            ->route('payment.show', $booking->code)
            ->with('status', 'Kursi berhasil dikunci. Selesaikan pembayaran dalam 15 menit.');
    }

    private function backUrl(Trip $trip, Request $request): string
    {
        $params = [];

        foreach (['origin', 'destination', 'date', 'passengers', 'mode'] as $key) {
            $value = $request->query($key);

            if (is_scalar($value) && $value !== '') {
                $params[$key] = $value;
            }
        }

        $params['mode'] = $trip->bus->service_mode;

        if (isset($params['origin'], $params['destination'], $params['date'])
            && filter_var($params['origin'], FILTER_VALIDATE_INT)
            && filter_var($params['destination'], FILTER_VALIDATE_INT)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $params['date'])) {
            return route('search', $params);
        }

        if ($trip->route->origin && $trip->route->destination) {
            return route('route.landing', [
                'origin'      => $trip->route->origin->slug,
                'destination' => $trip->route->destination->slug,
            ]);
        }

        return route('home');
    }
}
