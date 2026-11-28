@extends('layouts.app')

@section('title', 'Bus '.$origin->name.' ke '.$destination->name.' - Jadwal & Harga Tiket')
@section('meta_description', 'Lihat jadwal, harga tiket, operator, dan titik naik turun bus '.$origin->name.' ke '.$destination->name.'. Pesan tiket bus online dengan mudah.')
@section('canonical', route('route.landing', ['origin' => $origin->slug, 'destination' => $destination->slug]))

@section('structured_data')
<script type="application/ld+json">
@json($structured)
</script>
@endsection

@section('content')
<section class="container page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Beranda</a>
        <span>/</span>
        <span>{{ $origin->name }}</span>
        <span>/</span>
        <span>{{ $destination->name }}</span>
    </nav>

    <h1>Bus {{ $origin->name }} ke {{ $destination->name }}</h1>
    <p class="lead">
        Bandingkan jadwal dan harga tiket bus {{ $origin->name }} &ndash; {{ $destination->name }} dari berbagai operator.
        Lihat titik naik dan turun sebelum memesan.
    </p>

    <div class="card route-search-card">
        <form method="GET" action="{{ route('search') }}">
            <input type="hidden" name="origin" value="{{ $origin->id }}">
            <input type="hidden" name="destination" value="{{ $destination->id }}">

            <div class="search-grid compact">
                <label class="field">
                    Tanggal berangkat
                    <input type="date" name="date" value="{{ $today }}" min="{{ $today }}" required>
                </label>
                <label class="field">
                    Penumpang
                    <select name="passengers">
                        @for ($n = 1; $n <= 6; $n++)
                            <option value="{{ $n }}" @selected($n === 1)>{{ $n }} orang</option>
                        @endfor
                    </select>
                </label>
                <button class="btn" type="submit">Cari tiket</button>
            </div>
        </form>
    </div>

    @if ($trips->isNotEmpty())
        <div class="summary">
            <span><strong>{{ $trips->count() }}</strong> perjalanan 7 hari ke depan</span>
            <span><strong>{{ $trips->pluck('operator_name')->unique()->count() }}</strong> operator</span>
            <span>Mulai <strong>Rp {{ number_format($trips->min('base_price'), 0, ',', '.') }}</strong></span>
        </div>

        <h2>Jadwal perjalanan</h2>
        <div class="route-trip-list">
            @foreach ($trips as $trip)
                @php
                    $originTz = $origin->timezone ?: env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
                    $destinationTz = $destination->timezone ?: $originTz;
                    $depart = $trip->departure_at->copy()->setTimezone($originTz);
                    $arrive = $trip->arrival_at->copy()->setTimezone($destinationTz);
                @endphp

                <article class="trip-card">
                    <div class="trip-main">
                        <div class="trip-operator">{{ $trip->operator_name }}</div>
                        <div class="muted small">{{ $depart->translatedFormat('D, d M Y') }}</div>
                        <div class="trip-time">{{ $depart->format('H:i') }} <span>&rarr;</span> {{ $arrive->format('H:i') }}</div>
                        <div class="trip-meta">
                            <span>{{ (int) round($trip->duration_minutes) }} menit</span>
                            <span>Sisa {{ $trip->available_seats }} kursi</span>
                        </div>
                    </div>
                    <div class="trip-side">
                        <div class="price">Rp {{ number_format($trip->base_price, 0, ',', '.') }}</div>
                        <a class="btn" href="{{ route('trip.detail', [
                            'trip' => $trip->id,
                            'origin' => $origin->id,
                            'destination' => $destination->id,
                            'date' => $depart->toDateString(),
                            'passengers' => 1,
                        ]) }}">Lihat detail</a>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="card empty">
            <h2>Belum ada jadwal</h2>
            <p>Belum ada perjalanan aktif untuk rute ini dalam 7 hari ke depan.</p>
            <a class="btn" href="{{ route('search', ['origin' => $origin->id, 'destination' => $destination->id, 'date' => $today, 'passengers' => 1]) }}">Coba tanggal lain</a>
        </div>
    @endif

    @if ($related->isNotEmpty())
        <section class="section">
            <h2>Rute lain dari {{ $origin->name }}</h2>
            <div class="chips">
                @foreach ($related as $city)
                    <a class="chip" href="{{ route('route.landing', ['origin' => $origin->slug, 'destination' => $city->slug]) }}">
                        {{ $origin->name }} - {{ $city->name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <h2>Pertanyaan umum</h2>
        <div class="faq-list">
            @foreach ($faqs as $faq)
                <details>
                    <summary>{{ $faq['question'] }}</summary>
                    <p>{{ $faq['answer'] }}</p>
                </details>
            @endforeach
        </div>
    </section>
</section>
@endsection
