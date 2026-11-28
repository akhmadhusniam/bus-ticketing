@extends('layouts.app')

@php
    $trip = $booking->trip;
    $displayTimezone = env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
    $originTimezone = $trip->route->origin?->timezone ?: $displayTimezone;
    $destinationTimezone = $trip->route->destination?->timezone ?: $originTimezone;

    $boardingRouteStop = $trip->route->stops->firstWhere('bus_stop_id', $booking->boarding_stop_id);
    $droppingRouteStop = $trip->route->stops->firstWhere('bus_stop_id', $booking->dropping_stop_id);

    $depart = $trip->departure_at->copy()->setTimezone($originTimezone);
    $arrive = $trip->arrival_at->copy()->setTimezone($destinationTimezone);
    $boardingTime = $boardingRouteStop
        ? \App\Support\Schedule::timeAt($trip, $boardingRouteStop)
        : $depart;
    $droppingTime = $droppingRouteStop
        ? \App\Support\Schedule::timeAt($trip, $droppingRouteStop)
        : $arrive;

    $statuses = [
        'pending'   => 'Menunggu pembayaran',
        'paid'      => 'Lunas',
        'expired'   => 'Kedaluwarsa',
        'cancelled' => 'Dibatalkan',
        'refunded'  => 'Dikembalikan',
    ];
    $statusLabel = $statuses[$booking->status] ?? ucfirst($booking->status);
@endphp

@section('title', 'E-tiket '.$booking->code)
@section('meta_description', 'Detail e-tiket bus dengan kode booking '.$booking->code.'.')

@section('content')
<section class="container page ticket-page">
    @if (session('status'))
        <div class="alert alert-success" role="status" data-toast-source="success">{{ session('status') }}</div>
    @endif

    @php
        $stepCurrent = $booking->status === 'paid' ? 5 : 4;
    @endphp

    <p><a href="{{ route('ticket.lookup') }}">&larr; Cek tiket lain</a></p>
    @if ($searchUrl ?? null)
        <p class="muted small"><a href="{{ $searchUrl }}">Lihat jadwal serupa</a></p>
    @endif

    <x-flow-stepper :current="$stepCurrent" :payment-done="$booking->status === 'paid'" />

    <div class="ticket-top">
        <div>
            <h1>E-tiket</h1>
            <p class="muted">Kode booking <strong>{{ $booking->code }}</strong></p>
        </div>
        <div class="ticket-actions">
            <span class="status status-{{ $booking->status }}">{{ $statusLabel }}</span>
            <button class="btn" type="button" onclick="window.print()">Cetak</button>
        </div>
    </div>

    @if ($booking->status === 'pending')
        <div class="alert" role="status">
            Pembayaran belum diterima. Selesaikan sebelum
            {{ $booking->expires_at?->copy()->setTimezone($displayTimezone)->translatedFormat('d M Y H:i') }}.
            <a class="btn btn-primary btn-small" href="{{ route('payment.show', $booking->code) }}">Lanjutkan pembayaran</a>
        </div>
    @endif

    <div class="card ticket-card">
        <div class="ticket-section">
            <h2>{{ $trip->bus->operator->name }}</h2>
            <p class="muted">{{ $trip->bus->layout->name }} &middot; {{ ucfirst(str_replace('_', ' ', $trip->bus->bus_type)) }}</p>

            <div class="ticket-route">
                <div>
                    <span class="muted small">Naik</span>
                    <strong>{{ $boardingTime->format('H:i') }}</strong>
                    <div>{{ $booking->boardingStop->name }}</div>
                </div>
                <div class="ticket-arrow">&rarr;</div>
                <div>
                    <span class="muted small">Turun</span>
                    <strong>{{ $droppingTime->format('H:i') }}</strong>
                    <div>{{ $booking->droppingStop->name }}</div>
                </div>
            </div>

            <p class="muted small">
                {{ $depart->translatedFormat('l, d F Y') }} &middot;
                {{ $trip->route->origin->name }} ke {{ $trip->route->destination->name }}
            </p>
        </div>

        <div class="ticket-section">
            <h2>Data penumpang</h2>
            <table class="table">
                <thead>
                    <tr><th>Kursi</th><th>Nama</th><th>Umur</th><th>Jenis kelamin</th></tr>
                </thead>
                <tbody>
                    @foreach ($booking->passengers as $passenger)
                        <tr>
                            <td><strong>{{ $passenger->seat->seat_label }}</strong></td>
                            <td>{{ $passenger->name }}</td>
                            <td>{{ $passenger->age ?? '-' }}</td>
                            <td>{{ $passenger->gender ? ucfirst($passenger->gender) : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ticket-section">
            <h2>Pembayaran</h2>
            <dl class="info-list">
                <dt>Total</dt>
                <dd><strong>Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</strong> {{ $booking->currency }}</dd>
                <dt>Status</dt><dd>{{ $statusLabel }}</dd>
                @if ($payment)
                    <dt>Metode</dt><dd>{{ strtoupper($payment->method ?? $payment->provider) }}</dd>
                    <dt>Referensi</dt><dd>{{ $payment->provider_ref ?? '-' }}</dd>
                    <dt>Dibayar</dt><dd>{{ $payment->paid_at?->copy()->setTimezone($displayTimezone)->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                @endif
            </dl>
        </div>
    </div>

    <p class="muted small">Tunjukkan kode booking ini kepada petugas di titik naik.</p>
</section>
@endsection
