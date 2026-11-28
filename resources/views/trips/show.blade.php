@extends('layouts.app')

@php
    $originTimezone = $trip->route->origin?->timezone ?: env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
    $destinationTimezone = $trip->route->destination?->timezone ?: $originTimezone;
    $depart = $trip->departure_at->copy()->setTimezone($originTimezone);
    $arrive = $trip->arrival_at->copy()->setTimezone($destinationTimezone);
@endphp

@section('title', 'Detail '.$trip->route->origin->name.' - '.$trip->route->destination->name)
@section('meta_description', 'Detail perjalanan '.$trip->bus->operator->name.' dari '.$trip->route->origin->name.' ke '.$trip->route->destination->name.': jadwal, harga, fasilitas, dan titik naik turun.')
@section('canonical', route('trip.detail', ['trip' => $trip->id]))

@section('content')
<section class="container page">
    @if (session('error'))
        <div class="alert" role="alert">{{ session('error') }}</div>
    @endif

    <p><a href="{{ $backUrl }}">&larr; Kembali ke hasil pencarian</a></p>

    <div class="detail-header">
        <div>
            <h1>
                {{ $trip->bus->operator->name }}
                <span class="badge">{{ $trip->bus->service_mode === 'shuttle' ? 'Shuttle' : 'Bus' }}</span>
            </h1>
            <p class="muted">{{ $trip->route->origin->name }} &rarr; {{ $trip->route->destination->name }}</p>
        </div>
        <div class="detail-price">
            <div class="price">Rp {{ number_format($trip->base_price, 0, ',', '.') }}</div>
            <div class="muted small">per kursi</div>
        </div>
    </div>

    <div class="card detail-summary">
        <div>
            <span class="muted small">Berangkat</span>
            <strong>{{ $depart->translatedFormat('D, d M Y · H:i') }}</strong>
            <span class="muted small">{{ $originTimezone }}</span>
        </div>
        <div>
            <span class="muted small">Tiba</span>
            <strong>{{ $arrive->translatedFormat('D, d M Y · H:i') }}</strong>
            <span class="muted small">{{ $destinationTimezone }}</span>
        </div>
        <div>
            <span class="muted small">Durasi</span>
            <strong>{{ intdiv($duration, 60) }} jam {{ $duration % 60 }} menit</strong>
        </div>
        <div>
            <span class="muted small">Kapasitas kursi</span>
            <strong>{{ $capacity }} kursi &middot; tersedia {{ $availableSeats }}</strong>
        </div>
    </div>

    <div class="results-layout detail-layout">
        <div>
            <div class="card">
                <h2>Titik naik dan turun</h2>
                <p class="muted small">Waktu di bawah sudah disesuaikan dengan timezone kota masing-masing.</p>

                <ol class="timeline">
                    @foreach ($stops as $stop)
                        @php($time = \App\Support\Schedule::timeAt($trip, $stop))
                        <li>
                            <div class="timeline-time">{{ $time->format('H:i') }}</div>
                            <div class="timeline-body">
                                <strong>{{ \App\Support\Schedule::stopName($stop) }}</strong>
                                <div class="muted small">
                                    {{ \App\Support\Schedule::cityName($stop) }}
                                    @if ($stop->address), {{ $stop->address }}@endif
                                </div>
                                <div class="badges">
                                    @if ($stop->is_boarding)<span class="badge">Bisa naik</span>@endif
                                    @if ($stop->is_dropping)<span class="badge">Bisa turun</span>@endif
                                    @if (! $stop->is_boarding && ! $stop->is_dropping)<span class="badge">Persinggahan</span>@endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <aside>
            <div class="card">
                <h2>Informasi bus</h2>
                <dl class="info-list">
                    <dt>Operator</dt><dd>{{ $trip->bus->operator->name }}</dd>
                    <dt>Tipe bus</dt><dd>{{ ucfirst(str_replace('_', ' ', $trip->bus->bus_type)) }}</dd>
                    <dt>Layout</dt><dd>{{ $trip->bus->layout->name }}</dd>
                    <dt>Jarak</dt><dd>{{ $distance ? $distance.' km' : '-' }}</dd>
                </dl>

                <h3>Fasilitas</h3>
                <div class="badges">
                    @forelse ($amenities as $amenity)
                        <span class="badge">{{ ucfirst(str_replace('_', ' ', $amenity)) }}</span>
                    @empty
                        <span class="muted small">Belum ada data fasilitas.</span>
                    @endforelse
                </div>

                @if ($availableSeats > 0 && $trip->status === 'scheduled' && $trip->departure_at->isFuture())
                    <a class="btn btn-primary btn-block" href="{{ $bookingUrl }}">Lanjut pesan</a>
                    <p class="muted small">Pilih kursi, isi data penumpang, lalu bayar dalam 15 menit.</p>
                @else
                    <button class="btn btn-block" type="button" disabled>Kursi tidak tersedia</button>
                    <p class="muted small">Perjalanan ini sudah penuh atau tidak dapat dipesan.</p>
                @endif
            </div>

            <p><a href="{{ route('route.landing', ['origin' => $trip->route->origin->slug, 'destination' => $trip->route->destination->slug]) }}">Lihat semua perjalanan rute ini &rarr;</a></p>
        </aside>
    </div>
</section>
@endsection
