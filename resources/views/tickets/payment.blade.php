@extends('layouts.app')

@php
    $trip = $booking->trip;
    $originTimezone = $trip->route->origin?->timezone ?: env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
    $depart = $trip->departure_at->copy()->setTimezone($originTimezone);
@endphp

@section('title', 'Pembayaran '.$booking->code)
@section('meta_description', 'Selesaikan pembayaran simulasi untuk kode booking '.$booking->code.'.')
@section('canonical', route('payment.show', $booking->code))

@section('content')
<section class="container page">
    @if (session('error'))
        <div class="alert" role="alert" data-toast-source="error">{{ session('error') }}</div>
    @endif

    <p><a href="{{ $backUrl }}">&larr; Ubah pesanan</a></p>

    <ol class="steps" aria-label="Langkah pemesanan">
        <li class="is-done"><span>1</span> Pilih kursi &amp; data penumpang</li>
        <li class="is-active"><span>2</span> Pembayaran</li>
        <li><span>3</span> E-tiket</li>
    </ol>

    <div class="detail-header">
        <div>
            <h1>Selesaikan pembayaran</h1>
            <p class="muted">Kode booking <strong>{{ $booking->code }}</strong></p>
        </div>
        <div class="payment-timer" role="timer" aria-live="polite">
            <span class="muted small">Kursi dikunci selama</span>
            <strong id="countdown">15:00</strong>
        </div>
    </div>

    <div class="alert" role="status">
        Ini adalah pembayaran simulasi untuk demo. Tidak ada uang sungguhan yang ditagihkan.
    </div>

    @if ($errors->any())
        <div class="alert" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('payment.pay', $booking->code) }}" id="payment-form">
        @csrf

        <div class="booking-layout">
            <div>
                <div class="card">
                    <h2>Metode pembayaran</h2>
                    <div class="payment-methods">
                        @foreach ($methods as $key => $method)
                            <label class="payment-method">
                                <input type="radio" name="method" value="{{ $key }}"
                                       @checked(old('method', 'qris') === $key)>
                                <span class="payment-icon">{{ $method['icon'] }}</span>
                                <span class="payment-copy">
                                    <strong>{{ $method['label'] }}</strong>
                                    <span class="muted small">{{ $method['detail'] }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <button class="btn btn-primary btn-block" type="submit">
                        Bayar Rp {{ number_format($booking->total_amount, 0, ',', '.') }}
                    </button>
                    <p class="muted small">Setelah bayar, e-tiket langsung tersedia dan kursi menjadi milik Anda.</p>
                </div>
            </div>

            <aside>
                <div class="card">
                    <h2>Ringkasan pesanan</h2>
                    <dl class="info-list">
                        <dt>Operator</dt><dd>{{ $trip->bus->operator->name }}</dd>
                        <dt>Rute</dt><dd>{{ $trip->route->origin->name }} &rarr; {{ $trip->route->destination->name }}</dd>
                        <dt>Berangkat</dt><dd>{{ $depart->translatedFormat('D, d M Y · H:i') }}</dd>
                        <dt>Naik di</dt><dd>{{ $booking->boardingStop->name }}</dd>
                        <dt>Turun di</dt><dd>{{ $booking->droppingStop->name }}</dd>
                        <dt>Pemesan</dt><dd>{{ $booking->contact_name }}</dd>
                        <dt>Email</dt><dd>{{ $booking->contact_email }}</dd>
                    </dl>

                    <h3>Penumpang</h3>
                    <ul class="passenger-list">
                        @foreach ($booking->passengers as $passenger)
                            <li><strong>{{ $passenger->seat->seat_label }}</strong> &middot; {{ $passenger->name }}</li>
                        @endforeach
                    </ul>

                    <div class="summary-total">
                        <span>Total bayar</span>
                        <strong>Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const el = document.getElementById('countdown');
        if (!el) return;

        let left = {{ (int) $secondsLeft }};
        const tick = () => {
            const minutes = Math.floor(left / 60);
            const seconds = left % 60;
            el.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            if (left > 0) {
                left--;
                window.setTimeout(tick, 1000);
            } else {
                el.textContent = '00:00';
                const button = document.querySelector('#payment-form button[type="submit"]');
                if (button) button.disabled = true;
            }
        };
        tick();
    })();
</script>
@endpush
