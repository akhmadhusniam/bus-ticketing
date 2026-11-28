<?php

namespace App\Http\Controllers;

use App\Models\Trip;

class TripDetailController extends Controller
{
    public function show(Trip $trip)
    {
        $trip->load([
            'route.origin',
            'route.destination',
            'route.stops.busStop.city',
            'bus.operator',
            'bus.layout',
            'seats',
        ]);

        return view('trips.show', [
            'trip'           => $trip,
            'stops'          => $trip->route->stops,
            'availableSeats' => $trip->seats->where('status', 'available')->count(),
            'duration'       => (int) $trip->route->duration_minutes,
            'distance'       => $trip->route->distance_km,
            'amenities'      => (array) $trip->bus->amenities,
            'backUrl'        => $this->backUrl($trip),
            'bookingUrl'     => route('booking.show', ['trip' => $trip->id] + $this->searchParams()),
        ]);
    }

    /** @return array<string, mixed> */
    private function searchParams(): array
    {
        $params = [];

        foreach (['origin', 'destination', 'date', 'passengers', 'mode'] as $key) {
            $value = request()->query($key);

            if (is_scalar($value) && $value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    private function backUrl(Trip $trip): string
    {
        $params = $this->searchParams();

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
