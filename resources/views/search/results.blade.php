@extends('layouts.app')

@php
    $filterKeys = [
        'operator',
        'bus_type',
        'time_window',
        'min_price',
        'max_price',
        'min_rating',
        'departure_from',
        'departure_to',
        'arrival_from',
        'arrival_to',
        'boarding_stop',
        'dropping_stop',
    ];

    $queryUrl = function (array $overrides = []) use (
        $origin, $destination, $date, $passengers, $filters, $filterKeys,
        $mode, $returnDate
    ) {
        $params = [
            'origin'      => $origin->id,
            'destination' => $destination->id,
            'date'        => $date,
            'passengers'  => $passengers,
            'sort'        => $filters['sort'],
            'mode'        => $mode,
        ];

        if ($returnDate !== null) {
            $params['return_date'] = $returnDate;
        }

        foreach ($filterKeys as $key) {
            $value = $filters[$key];
            if ($value !== null && $value !== '') {
                $params[$key] = $value;
            }
        }

        foreach ($filters['amenities'] as $index => $amenity) {
            $params['amenities['.$index.']'] = $amenity;
        }

        foreach ($overrides as $key => $value) {
            if ($key === 'amenities') {
                foreach (array_keys($params) as $paramKey) {
                    if (str_starts_with($paramKey, 'amenities[')) {
                        unset($params[$paramKey]);
                    }
                }
                if (is_array($value)) {
                    foreach (array_values($value) as $index => $amenity) {
                        $params['amenities['.$index.']'] = $amenity;
                    }
                }
                continue;
            }

            if ($value === null || $value === '') {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return route('search', $params);
    };

    $resetUrl = $queryUrl(array_fill_keys(
        array_merge($filterKeys, ['amenities', 'sort']),
        null
    ));

    $rangeLabel = function (?string $from, ?string $to): string {
        if ($from && $to) {
            return $from.' - '.$to;
        }
        if ($from) {
            return 'setelah '.$from;
        }
        return 'sebelum '.$to;
    };

    $activeFilters = [];

    if ($filters['operator'] !== null && isset($operators[$filters['operator']])) {
        $activeFilters[] = [
            'label' => 'Operator: '.$operators[$filters['operator']],
            'url'   => $queryUrl(['operator' => null]),
        ];
    }

    if ($filters['bus_type'] !== '') {
        $activeFilters[] = [
            'label' => 'Tipe: '.ucfirst(str_replace('_', ' ', $filters['bus_type'])),
            'url'   => $queryUrl(['bus_type' => null]),
        ];
    }

    if ($filters['departure_from'] !== null || $filters['departure_to'] !== null) {
        $activeFilters[] = [
            'label' => 'Berangkat '.$rangeLabel($filters['departure_from'], $filters['departure_to']),
            'url'   => $queryUrl(['departure_from' => null, 'departure_to' => null]),
        ];
    }

    if ($filters['arrival_from'] !== null || $filters['arrival_to'] !== null) {
        $activeFilters[] = [
            'label' => 'Tiba '.$rangeLabel($filters['arrival_from'], $filters['arrival_to']),
            'url'   => $queryUrl(['arrival_from' => null, 'arrival_to' => null]),
        ];
    }

    if ($filters['time_window'] !== '') {
        $activeFilters[] = [
            'label' => 'Jam: '.$filters['time_window'],
            'url'   => $queryUrl(['time_window' => null]),
        ];
    }

    if ($filters['min_price'] !== null) {
        $activeFilters[] = [
            'label' => 'Min Rp '.number_format($filters['min_price'], 0, ',', '.'),
            'url'   => $queryUrl(['min_price' => null]),
        ];
    }

    if ($filters['max_price'] !== null) {
        $activeFilters[] = [
            'label' => 'Max Rp '.number_format($filters['max_price'], 0, ',', '.'),
            'url'   => $queryUrl(['max_price' => null]),
        ];
    }

    if ($filters['min_rating'] !== null) {
        $activeFilters[] = [
            'label' => 'Rating '.$filters['min_rating'].'+',
            'url'   => $queryUrl(['min_rating' => null]),
        ];
    }

    foreach ($filters['amenities'] as $amenity) {
        $activeFilters[] = [
            'label' => 'Fasilitas: '.($amenities[$amenity] ?? ucfirst(str_replace('_', ' ', $amenity))),
            'url'   => $queryUrl([
                'amenities' => array_values(array_diff($filters['amenities'], [$amenity])),
            ]),
        ];
    }

    if ($filters['boarding_stop'] !== null && isset($boardingStops[$filters['boarding_stop']])) {
        $activeFilters[] = [
            'label' => 'Naik: '.$boardingStops[$filters['boarding_stop']],
            'url'   => $queryUrl(['boarding_stop' => null]),
        ];
    }

    if ($filters['dropping_stop'] !== null && isset($droppingStops[$filters['dropping_stop']])) {
        $activeFilters[] = [
            'label' => 'Turun: '.$droppingStops[$filters['dropping_stop']],
            'url'   => $queryUrl(['dropping_stop' => null]),
        ];
    }

    $activeCount = count($activeFilters);
    $sortLabel = 'Urutkan';

    foreach ($sortOptions as $option) {
        if ($option['value'] === $filters['sort']) {
            $sortLabel = $option['label'];
            break;
        }
    }
@endphp

@section('title', $origin->name.' - '.$destination->name)

@section('content')
<section class="container page">
    <div class="results-heading">
        <span class="section-kicker">{{ $mode === 'shuttle' ? 'Shuttle' : 'Bus' }}</span>
        <h1>{{ $origin->name }} &rarr; {{ $destination->name }}</h1>
        <p class="muted">
            {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }} &middot; {{ $passengers }} penumpang
        </p>
        <p class="muted small">
            <a href="{{ route('route.landing', ['origin' => $origin->slug, 'destination' => $destination->slug]) }}">
                Lihat halaman rute {{ $origin->name }} - {{ $destination->name }} &rarr;
            </a>
        </p>
    </div>

    <div class="trip-direction">
        <div class="direction-leg">
            <span>Pergi</span>
            <strong>{{ $origin->name }} &rarr; {{ $destination->name }}</strong>
            <small>{{ \Carbon\Carbon::parse($date)->translatedFormat('D, d M Y') }}</small>
        </div>
        @if ($returnDate !== null)
            <div class="direction-leg return">
                <span>Pulang</span>
                <strong>{{ $destination->name }} &rarr; {{ $origin->name }}</strong>
                <small>{{ \Carbon\Carbon::parse($returnDate)->translatedFormat('D, d M Y') }}</small>
            </div>
            <a class="btn btn-outline" href="{{ $returnUrl }}">Cari tiket pulang &rarr;</a>
        @endif
    </div>

    @if ($summary['count'] > 0)
        <div class="summary">
            <span>
                @if ($summary['filtered'] < $summary['count'])
                    <strong>{{ $summary['filtered'] }}</strong> dari {{ $summary['count'] }} perjalanan
                @else
                    <strong>{{ $summary['count'] }}</strong> perjalanan
                @endif
            </span>
            <span><strong>{{ $summary['operators'] }}</strong> operator</span>
            <span>Mulai <strong>Rp {{ number_format($summary['min_price'], 0, ',', '.') }}</strong></span>
            <span>Rata-rata {{ $summary['duration'] }} menit</span>
            @if ($summary['rating'] > 0)
                <span>Rating <strong>{{ $summary['rating'] }}</strong></span>
            @endif
        </div>
    @endif

    @if (count($activeFilters) > 0)
        <div class="active-filters">
            @foreach ($activeFilters as $filter)
                <a class="chip" href="{{ $filter['url'] }}">{{ $filter['label'] }} &times;</a>
            @endforeach
            <a class="reset-link" href="{{ $resetUrl }}">Hapus semua</a>
        </div>
    @endif

    <div class="mobile-filter-bar">
        <span class="mobile-filter-count">{{ $summary['filtered'] }} jadwal</span>
        <button type="button" class="mobile-filter-chip" data-filter-open data-filter-focus="sort"
                aria-controls="filter-panel" aria-expanded="false">
            Urutkan: {{ $sortLabel }}
        </button>
        <button type="button" class="mobile-filter-chip mobile-filter-primary" data-filter-open
                aria-controls="filter-panel" aria-expanded="false">
            Filter
            @if ($activeCount > 0)<span class="filter-count">({{ $activeCount }})</span>@endif
        </button>
    </div>
    <div class="filter-backdrop" data-filter-close aria-hidden="true"></div>

    <div class="results-layout">
        <aside class="card filter-card" id="filter-panel" aria-label="Filter hasil pencarian">
            <h2>Filter</h2>
            <div class="filter-drawer-head">
                <strong>Filter jadwal</strong>
                <button type="button" class="filter-drawer-close" data-filter-close aria-label="Tutup filter">&times;</button>
            </div>
            <form method="GET" action="{{ route('search') }}">
                <input type="hidden" name="origin" value="{{ $origin->id }}">
                <input type="hidden" name="destination" value="{{ $destination->id }}">
                <input type="hidden" name="date" value="{{ $date }}">
                @if ($returnDate !== null)
                    <input type="hidden" name="return_date" value="{{ $returnDate }}">
                @endif
                <input type="hidden" name="mode" value="{{ $mode }}">
                <input type="hidden" name="passengers" value="{{ $passengers }}">

                <label class="field">
                    Urutkan
                    <select name="sort">
                        @foreach ($sortOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($filters['sort'] === $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    Bus operator
                    <select name="operator">
                        <option value="" @selected($filters['operator'] === null)>Semua operator</option>
                        @foreach ($operators as $id => $name)
                            <option value="{{ $id }}" @selected($filters['operator'] === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    Tipe bus
                    <select name="bus_type">
                        <option value="" @selected($filters['bus_type'] === '')>Semua tipe</option>
                        @foreach ($busTypes as $option)
                            <option value="{{ $option['value'] }}" @selected($filters['bus_type'] === $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <fieldset class="filter-group">
                    <legend>Rentang waktu keberangkatan</legend>
                    <div class="field-row">
                        <label class="field">
                            Dari
                            <input type="time" name="departure_from" value="{{ $filters['departure_from'] ?? '' }}">
                        </label>
                        <label class="field">
                            Sampai
                            <input type="time" name="departure_to" value="{{ $filters['departure_to'] ?? '' }}">
                        </label>
                    </div>
                    <p class="hint">Waktu setempat kota asal ({{ $timezone }}).</p>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Waktu tiba</legend>
                    <div class="field-row">
                        <label class="field">
                            Dari
                            <input type="time" name="arrival_from" value="{{ $filters['arrival_from'] ?? '' }}">
                        </label>
                        <label class="field">
                            Sampai
                            <input type="time" name="arrival_to" value="{{ $filters['arrival_to'] ?? '' }}">
                        </label>
                    </div>
                    <p class="hint">Waktu setempat kota tujuan ({{ $destinationTimezone }}).</p>
                </fieldset>

                <label class="field">
                    Titik keberangkatan
                    <select name="boarding_stop">
                        <option value="" @selected($filters['boarding_stop'] === null)>Semua titik</option>
                        @foreach ($boardingStops as $id => $name)
                            <option value="{{ $id }}" @selected($filters['boarding_stop'] === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    Titik penurunan
                    <select name="dropping_stop">
                        <option value="" @selected($filters['dropping_stop'] === null)>Semua titik</option>
                        @foreach ($droppingStops as $id => $name)
                            <option value="{{ $id }}" @selected($filters['dropping_stop'] === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <fieldset class="filter-group">
                    <legend>Fitur / fasilitas</legend>
                    <div class="amenity-grid">
                        @forelse ($amenities as $key => $label)
                            <label class="check">
                                <input type="checkbox" name="amenities[]" value="{{ $key }}"
                                       @checked(in_array($key, $filters['amenities'], true))>
                                <span>{{ $label }}</span>
                            </label>
                        @empty
                            <p class="hint">Belum ada data fasilitas.</p>
                        @endforelse
                    </div>
                </fieldset>

                <label class="field">
                    Rating bus
                    <select name="min_rating">
                        <option value="" @selected($filters['min_rating'] === null)>Semua rating</option>
                        @foreach ($ratingOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($filters['min_rating'] === (float) $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <fieldset class="filter-group">
                    <legend>Rentang harga</legend>
                    <div class="field-row">
                        <label class="field">
                            Min (Rp)
                            <input type="number" name="min_price" min="1" step="1000"
                                   value="{{ $filters['min_price'] ?? '' }}" placeholder="0">
                        </label>
                        <label class="field">
                            Max (Rp)
                            <input type="number" name="max_price" min="1" step="1000"
                                   value="{{ $filters['max_price'] ?? '' }}" placeholder="150000">
                        </label>
                    </div>
                </fieldset>

                <div class="filter-actions">
                    <button class="btn" type="submit">Terapkan filter</button>
                    <a class="reset-link" href="{{ $resetUrl }}">Reset filter</a>
                </div>
            </form>
        </aside>

        <div class="results-list">
            @forelse ($trips as $trip)
                @php
                    $boarding = $trip->route->stops->first(fn ($stop) => $stop->is_boarding);
                    $dropping = $trip->route->stops->where('is_dropping', true)->last();
                    $depart = $trip->departure_at->copy()->setTimezone($timezone);
                    $arrive = $trip->arrival_at->copy()->setTimezone($destinationTimezone);
                    $boardingTime = $boarding
                        ? $trip->departure_at->copy()->setTimezone($timezone)->addMinutes($boarding->offset_minutes)->format('H:i')
                        : $depart->format('H:i');
                    $droppingTime = $dropping
                        ? $trip->departure_at->copy()->setTimezone($destinationTimezone)->addMinutes($dropping->offset_minutes)->format('H:i')
                        : $arrive->format('H:i');
                    $duration = (int) $trip->duration_minutes;
                @endphp

                <article class="trip-card">
                    <div class="trip-main">
                        <div class="trip-operator">
                            {{ $trip->operator_name }}
                            @if ((float) $trip->operator_rating > 0)
                                <span class="rating-badge">&#9733; {{ number_format((float) $trip->operator_rating, 1) }}</span>
                            @endif
                        </div>
                        <div class="muted small">
                            {{ $trip->service_mode === 'shuttle' ? 'Shuttle' : 'Bus' }} &middot;
                            {{ $trip->layout_name }} &middot; {{ ucfirst(str_replace('_', ' ', $trip->bus_type)) }}
                        </div>

                        <div class="trip-time">
                            {{ $depart->format('H:i') }} <span>&rarr;</span> {{ $arrive->format('H:i') }}
                        </div>

                        <div class="trip-stops">
                            <div><strong>{{ $boardingTime }}</strong> {{ $boarding?->busStop?->name ?? $origin->name }}</div>
                            <div><strong>{{ $droppingTime }}</strong> {{ $dropping?->busStop?->name ?? $destination->name }}</div>
                        </div>

                        <div class="trip-meta">
                            <span>{{ intdiv($duration, 60) }}j {{ $duration % 60 }}m</span>
                            @if ($trip->distance_km)
                                <span>{{ $trip->distance_km }} km</span>
                            @endif
                            <span>Kapasitas {{ $trip->total_seats }} kursi &middot; Sisa {{ $trip->available_seats }}</span>
                        </div>

                        <div class="badges">
                            @foreach ((array) $trip->amenities as $amenity)
                                <span class="badge">{{ ucfirst(str_replace('_', ' ', $amenity)) }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="trip-side">
                        <div class="price">Rp {{ number_format($trip->base_price, 0, ',', '.') }}</div>
                        <div class="muted small">per kursi</div>
                        @if ($trip->available_seats >= $passengers)
                            <a class="btn" href="{{ route('trip.detail', [
                                'trip' => $trip->id,
                                'origin' => $origin->id,
                                'destination' => $destination->id,
                                'date' => $date,
                                'passengers' => $passengers,
                            ]) }}">Lihat detail</a>
                        @else
                            <button class="btn" type="button" disabled>Kursi tidak cukup</button>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card empty">
                    @if ($mode === 'shuttle')
                        <h2>Belum ada shuttle</h2>
                        <p>Belum ada shuttle aktif untuk rute dan tanggal ini. Coba tanggal lain atau beralih ke bus.</p>
                        <a class="btn" href="{{ route('search', [
                            'origin'      => $origin->id,
                            'destination' => $destination->id,
                            'date'        => $date,
                            'passengers'  => $passengers,
                            'mode'        => 'bus',
                        ]) }}">Lihat bus</a>
                    @else
                        <h2>Belum ada perjalanan</h2>
                        <p>Ubah tanggal, hapus sebagian filter, atau pilih rute lain.</p>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <p><a href="{{ route('home') }}#cari">&larr; Ubah pencarian</a></p>
</section>
@endsection

@push('scripts')
<script>
    (function () {
        var panel = document.getElementById('filter-panel');
        if (!panel) {
            return;
        }

        var openers = Array.prototype.slice.call(document.querySelectorAll('[data-filter-open]'));
        var closers = Array.prototype.slice.call(document.querySelectorAll('[data-filter-close]'));
        var lastFocused = null;
        var previousOverflow = '';

        function setExpanded(expanded) {
            openers.forEach(function (button) {
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        }

        function openDrawer(button) {
            lastFocused = document.activeElement;
            previousOverflow = document.body.style.overflow;
            document.body.classList.add('filter-open');
            document.body.style.overflow = 'hidden';
            setExpanded(true);

            var focusTarget = button && button.getAttribute('data-filter-focus') === 'sort'
                ? panel.querySelector('select[name="sort"]')
                : panel.querySelector('.filter-drawer-close');

            window.setTimeout(function () {
                if (focusTarget) {
                    focusTarget.focus();
                }
            }, 220);
        }

        function closeDrawer() {
            document.body.classList.remove('filter-open');
            document.body.style.overflow = previousOverflow;
            setExpanded(false);

            if (lastFocused && typeof lastFocused.focus === 'function') {
                lastFocused.focus();
            }
        }

        openers.forEach(function (button) {
            button.addEventListener('click', function () {
                openDrawer(button);
            });
        });

        closers.forEach(function (button) {
            button.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && document.body.classList.contains('filter-open')) {
                closeDrawer();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 860 && document.body.classList.contains('filter-open')) {
                closeDrawer();
            }
        });
    })();
</script>
@endpush
