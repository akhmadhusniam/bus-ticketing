<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Trip;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SearchResultsController extends Controller
{
    private const SORTS = [
        ['value' => 'departure_asc', 'label' => 'Waktu berangkat'],
        ['value' => 'price_asc',     'label' => 'Harga termurah'],
        ['value' => 'price_desc',    'label' => 'Harga tertinggi'],
        ['value' => 'duration_asc',  'label' => 'Durasi tercepat'],
        ['value' => 'rating_desc',   'label' => 'Rating tertinggi'],
    ];

    private const BUS_TYPES = [
        ['value' => 'seater',       'label' => 'Seater'],
        ['value' => 'semi_sleeper', 'label' => 'Semi sleeper'],
        ['value' => 'sleeper',      'label' => 'Sleeper'],
    ];

    private const TIME_WINDOWS = [
        ['value' => 'morning',   'label' => 'Pagi (05.00-11.00)'],
        ['value' => 'afternoon', 'label' => 'Siang (11.00-15.00)'],
        ['value' => 'evening',   'label' => 'Sore (15.00-18.00)'],
        ['value' => 'night',     'label' => 'Malam (18.00-05.00)'],
    ];

    private const RATINGS = [
        ['value' => '4.0', 'label' => '4.0 ke atas'],
        ['value' => '4.5', 'label' => '4.5 ke atas'],
        ['value' => '4.8', 'label' => '4.8 ke atas'],
    ];

    public function index(Request $request)
    {
        $originId = $request->input('origin');
        $departureInput = $request->input('date');
        $originCity = is_scalar($originId) ? City::find($originId) : null;
        $timezone = $originCity?->timezone ?? env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
        $today = now($timezone)->toDateString();

        $data = $request->validate([
            'origin'      => ['required', 'exists:cities,id'],
            'destination' => ['required', 'exists:cities,id', 'different:origin'],
            'date'        => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($today) {
                    if (Carbon::parse($value)->toDateString() < $today) {
                        $fail('Tanggal berangkat tidak boleh sebelum hari ini.');
                    }
                },
            ],
            'return_date' => [
                'sometimes',
                'nullable',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($departureInput) {
                    if ($value && $departureInput
                        && Carbon::parse($value)->toDateString() < Carbon::parse($departureInput)->toDateString()) {
                        $fail('Tanggal pulang tidak boleh sebelum tanggal berangkat.');
                    }
                },
            ],
            'passengers'  => ['required', 'integer', 'between:1,6'],
            'mode'        => ['sometimes', 'in:bus,shuttle'],
        ], [
            'required'              => ':attribute wajib diisi.',
            'exists'                => ':attribute tidak valid.',
            'destination.different' => 'Kota tujuan harus berbeda dari kota asal.',
            'date.date'             => 'Tanggal berangkat tidak valid.',
            'return_date.date'      => 'Tanggal pulang tidak valid.',
            'passengers.between'    => 'Jumlah penumpang harus 1 sampai 6.',
            'mode.in'               => 'Moda transportasi tidak valid.',
        ], [
            'origin'      => 'Kota asal',
            'destination' => 'Kota tujuan',
            'date'        => 'Tanggal berangkat',
            'return_date' => 'Tanggal pulang',
            'passengers'  => 'Jumlah penumpang',
            'mode'        => 'Moda transportasi',
        ]);

        $mode = ($data['mode'] ?? null) === 'shuttle' ? 'shuttle' : 'bus';
        $returnDate = ! empty($data['return_date']) ? $data['return_date'] : null;
        $returnUrl = $returnDate !== null
            ? route('search', [
                'origin'      => $data['destination'],
                'destination' => $data['origin'],
                'date'        => $returnDate,
                'passengers'  => $data['passengers'],
                'mode'        => $mode,
            ])
            : null;

        $originCity = City::findOrFail($data['origin']);
        $destinationCity = City::findOrFail($data['destination']);
        $destinationTimezone = $destinationCity->timezone ?: $timezone;

        $start = Carbon::parse($data['date'], $timezone)->startOfDay()->utc();
        $end = $start->copy()->addDay();

        $all = Trip::query()
            ->select([
                'trips.*',
                'routes.duration_minutes',
                'routes.distance_km',
                'operators.id as operator_id',
                'operators.name as operator_name',
                'operators.rating as operator_rating',
                'buses.bus_type',
                'buses.service_mode',
                'buses.amenities',
                'bus_layouts.name as layout_name',
                'bus_layouts.total_seats',
            ])
            ->join('routes', 'routes.id', '=', 'trips.route_id')
            ->join('buses', 'buses.id', '=', 'trips.bus_id')
            ->join('operators', 'operators.id', '=', 'buses.operator_id')
            ->join('bus_layouts', 'bus_layouts.id', '=', 'buses.bus_layout_id')
            ->where('routes.origin_city_id', $data['origin'])
            ->where('routes.destination_city_id', $data['destination'])
            ->where('routes.is_active', true)
            ->where('buses.is_active', true)
            ->where('buses.service_mode', $mode)
            ->where('operators.is_active', true)
            ->where('trips.status', 'scheduled')
            ->where('trips.departure_at', '>=', $start)
            ->where('trips.departure_at', '<', $end)
            ->with(['route.stops.busStop'])
            ->withCount(['seats as available_seats' => function ($query) {
                $query->where('status', 'available');
            }])
            ->orderBy('trips.departure_at')
            ->get();

        $summary = [
            'count'     => $all->count(),
            'operators' => $all->pluck('operator_id')->unique()->count(),
            'min_price' => $all->min('base_price'),
            'max_price' => $all->max('base_price'),
            'first'     => $all->sortBy('departure_at')->first()?->departure_at,
            'last'      => $all->sortByDesc('departure_at')->first()?->departure_at,
            'duration'  => (int) round($all->avg('duration_minutes') ?: 0),
            'rating'    => round((float) ($all->avg('operator_rating') ?? 0), 1),
        ];

        $filters = $this->normalizeFilters($request);

        $trips = $all->filter(function (Trip $trip) use ($filters, $timezone, $destinationTimezone) {
            if ($filters['operator'] !== null && (int) $trip->operator_id !== $filters['operator']) {
                return false;
            }
            if ($filters['bus_type'] !== '' && $trip->bus_type !== $filters['bus_type']) {
                return false;
            }
            if ($filters['min_price'] !== null && (int) $trip->base_price < $filters['min_price']) {
                return false;
            }
            if ($filters['max_price'] !== null && (int) $trip->base_price > $filters['max_price']) {
                return false;
            }
            if ($filters['min_rating'] !== null
                && (float) $trip->operator_rating < $filters['min_rating']) {
                return false;
            }
            if ($filters['amenities'] !== []) {
                $tripAmenities = (array) $trip->amenities;
                foreach ($filters['amenities'] as $amenity) {
                    if (! in_array($amenity, $tripAmenities, true)) {
                        return false;
                    }
                }
            }
            if ($filters['boarding_stop'] !== null
                && ! $trip->route->stops->contains(
                    fn ($stop) => $stop->is_boarding
                        && (int) $stop->bus_stop_id === $filters['boarding_stop']
                )) {
                return false;
            }
            if ($filters['dropping_stop'] !== null
                && ! $trip->route->stops->contains(
                    fn ($stop) => $stop->is_dropping
                        && (int) $stop->bus_stop_id === $filters['dropping_stop']
                )) {
                return false;
            }

            $depart = $trip->departure_at->copy()->setTimezone($timezone);
            $arrive = $trip->arrival_at->copy()->setTimezone($destinationTimezone);

            if ($filters['time_window'] !== ''
                && ! $this->matchesTimeWindow($depart->hour, $filters['time_window'])) {
                return false;
            }
            if (! $this->matchesTimeRange($depart, $filters['departure_from'], $filters['departure_to'])) {
                return false;
            }
            if (! $this->matchesTimeRange($arrive, $filters['arrival_from'], $filters['arrival_to'])) {
                return false;
            }

            return true;
        });

        $summary['filtered'] = $trips->count();

        return view('search.results', [
            'origin'              => $originCity,
            'destination'         => $destinationCity,
            'date'                => $data['date'],
            'returnDate'          => $returnDate,
            'returnUrl'           => $returnUrl,
            'mode'                => $mode,
            'passengers'          => (int) $data['passengers'],
            'trips'               => $this->sortTrips($trips, $filters['sort'])->values(),
            'summary'             => $summary,
            'filters'             => $filters,
            'operators'           => $all->pluck('operator_name', 'operator_id')->unique()->sort()->all(),
            'amenities'           => $this->amenityOptions($all),
            'boardingStops'       => $this->stopOptions($all, true),
            'droppingStops'       => $this->stopOptions($all, false),
            'sortOptions'         => self::SORTS,
            'busTypes'            => self::BUS_TYPES,
            'timeWindows'         => self::TIME_WINDOWS,
            'ratingOptions'       => self::RATINGS,
            'timezone'            => $timezone,
            'destinationTimezone' => $destinationTimezone,
        ]);
    }

    private function normalizeFilters(Request $request): array
    {
        $sort = (string) $request->input('sort', '');
        $busType = (string) $request->input('bus_type', '');
        $window = (string) $request->input('time_window', '');

        $operator = filter_var($request->input('operator'), FILTER_VALIDATE_INT);
        $boardingStop = filter_var($request->input('boarding_stop'), FILTER_VALIDATE_INT);
        $droppingStop = filter_var($request->input('dropping_stop'), FILTER_VALIDATE_INT);
        $minPrice = filter_var(
            $request->input('min_price'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $maxPrice = filter_var(
            $request->input('max_price'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $minRating = filter_var(
            $request->input('min_rating'),
            FILTER_VALIDATE_FLOAT,
            ['options' => ['min_range' => 0, 'max_range' => 5]]
        );

        $amenityInput = $request->input('amenities', []);
        if (! is_array($amenityInput)) {
            $amenityInput = [$amenityInput];
        }
        $legacyAmenity = $request->input('amenity');
        if (is_string($legacyAmenity) && $legacyAmenity !== '') {
            $amenityInput[] = $legacyAmenity;
        }
        $amenities = array_values(array_unique(array_filter(
            array_map(fn ($value) => is_scalar($value) ? (string) $value : '', $amenityInput),
            fn ($value) => preg_match('/^[a-z_]+$/', $value) === 1
        )));

        return [
            'sort'            => in_array($sort, array_column(self::SORTS, 'value'), true)
                ? $sort : 'departure_asc',
            'bus_type'        => in_array($busType, array_column(self::BUS_TYPES, 'value'), true)
                ? $busType : '',
            'time_window'     => in_array($window, array_column(self::TIME_WINDOWS, 'value'), true)
                ? $window : '',
            'amenities'       => $amenities,
            'operator'        => $operator === false ? null : (int) $operator,
            'boarding_stop'   => $boardingStop === false ? null : (int) $boardingStop,
            'dropping_stop'   => $droppingStop === false ? null : (int) $droppingStop,
            'min_price'       => $minPrice === false ? null : (int) $minPrice,
            'max_price'       => $maxPrice === false ? null : (int) $maxPrice,
            'min_rating'      => $minRating === false ? null : round((float) $minRating, 1),
            'departure_from'  => $this->normalizeTime($request->input('departure_from')),
            'departure_to'    => $this->normalizeTime($request->input('departure_to')),
            'arrival_from'    => $this->normalizeTime($request->input('arrival_from')),
            'arrival_to'      => $this->normalizeTime($request->input('arrival_to')),
        ];
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private function timeToMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    }

    private function matchesTimeRange(Carbon $time, ?string $from, ?string $to): bool
    {
        if ($from === null && $to === null) {
            return true;
        }

        $minutes = $time->hour * 60 + $time->minute;
        $fromMinutes = $from !== null ? $this->timeToMinutes($from) : null;
        $toMinutes = $to !== null ? $this->timeToMinutes($to) : null;

        if ($fromMinutes !== null && $toMinutes === null) {
            return $minutes >= $fromMinutes;
        }
        if ($fromMinutes === null && $toMinutes !== null) {
            return $minutes <= $toMinutes;
        }

        // Rentang melewati tengah malam, misalnya 22.00-05.00.
        if ($fromMinutes > $toMinutes) {
            return $minutes >= $fromMinutes || $minutes <= $toMinutes;
        }

        return $minutes >= $fromMinutes && $minutes <= $toMinutes;
    }

    private function amenityOptions(Collection $trips): array
    {
        $options = [];
        foreach ($trips as $trip) {
            foreach ((array) $trip->amenities as $amenity) {
                $options[$amenity] = ucfirst(str_replace('_', ' ', $amenity));
            }
        }
        ksort($options);

        return $options;
    }

    private function stopOptions(Collection $trips, bool $boarding): array
    {
        $options = [];
        foreach ($trips as $trip) {
            foreach ($trip->route->stops as $stop) {
                $isValid = $boarding ? $stop->is_boarding : $stop->is_dropping;
                if ($isValid && $stop->busStop) {
                    $options[$stop->bus_stop_id] = $stop->busStop->name;
                }
            }
        }
        ksort($options);

        return $options;
    }

    private function matchesTimeWindow(int $hour, string $window): bool
    {
        return match ($window) {
            'morning'   => $hour >= 5 && $hour < 11,
            'afternoon' => $hour >= 11 && $hour < 15,
            'evening'   => $hour >= 15 && $hour < 18,
            'night'     => $hour >= 18 || $hour < 5,
            default     => true,
        };
    }

    private function sortTrips(Collection $trips, string $sort): Collection
    {
        return match ($sort) {
            'price_asc'    => $trips->sortBy('base_price'),
            'price_desc'   => $trips->sortByDesc('base_price'),
            'duration_asc' => $trips->sortBy('duration_minutes'),
            'rating_desc'  => $trips->sortByDesc('operator_rating'),
            default        => $trips->sortBy('departure_at'),
        };
    }
}
