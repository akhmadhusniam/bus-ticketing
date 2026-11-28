@extends('layouts.app')

@php
    $originTimezone = $trip->route->origin?->timezone ?: env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
    $destinationTimezone = $trip->route->destination?->timezone ?: $originTimezone;
    $depart = $trip->departure_at->copy()->setTimezone($originTimezone);
    $arrive = $trip->arrival_at->copy()->setTimezone($destinationTimezone);
    $passengerForms = max($passengerCount, count(old('passenger', [])));
    $limit = min(8, max(1, $seatCount));
@endphp

@section('title', 'Pesan tiket '.$trip->bus->operator->name)
@section('meta_description', 'Pilih kursi dan isi data penumpang untuk perjalanan '.$trip->route->origin->name.' ke '.$trip->route->destination->name.'.')
@section('canonical', route('booking.show', $trip))

@section('content')
<section class="container page">
    @if (session('error'))
        <div class="alert" role="alert" data-toast-source="error">{{ session('error') }}</div>
    @endif

    <p><a href="{{ $backUrl }}">&larr; Kembali ke detail trip</a></p>

    <x-flow-stepper :current="3" />

    <div class="detail-header">
        <div>
            <h1>Pesan tiket</h1>
            <p class="muted">{{ $trip->bus->operator->name }} &middot; {{ $trip->route->origin->name }} &rarr; {{ $trip->route->destination->name }}</p>
        </div>
        <div class="detail-price">
            <div class="price">Mulai Rp {{ number_format($estimate, 0, ',', '.') }}</div>
            <div class="muted small">untuk {{ $passengerCount }} penumpang</div>
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
            <span class="muted small">Kursi tersedia</span>
            <strong>{{ $seatCount }} kursi</strong>
        </div>
        <div>
            <span class="muted small">Layout</span>
            <strong>{{ $trip->bus->layout->name }}</strong>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert" role="alert">
            <strong>Lengkapi data berikut:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('booking.store', $trip) }}" id="booking-form">
        @csrf

        <div class="booking-layout">
            <div>
                <div class="card">
                    <div class="card-heading">
                        <div>
                            <h2 id="seat-map-title">Pilih kursi</h2>
                            <p class="muted small">
                                Kapasitas {{ $capacity }} kursi &middot; maksimal {{ $limit }} kursi per pesanan.
                                @if ($priceRange)
                                    &middot;
                                    @if ($priceRange['min'] === $priceRange['max'])
                                        Rp {{ number_format($priceRange['min'], 0, ',', '.') }} per kursi
                                    @else
                                        Rp {{ number_format($priceRange['min'], 0, ',', '.') }} &ndash;
                                        Rp {{ number_format($priceRange['max'], 0, ',', '.') }} per kursi
                                    @endif
                                @endif
                            </p>
                        </div>
                        <span class="badge" id="seat-counter" aria-live="polite">0 dari {{ $limit }} kursi dipilih</span>
                    </div>

                    @if ($capacity !== $layoutSeatCount)
                        <div class="alert" role="alert">
                            Konfigurasi kursi belum sinkron: kapasitas {{ $capacity }}, templat {{ $layoutSeatCount }} kursi.
                        </div>
                    @endif

                    <div class="seat-legend" aria-labelledby="seat-legend-title">
                        <span class="visually-hidden" id="seat-legend-title">Keterangan status kursi</span>
                        <span><i class="dot dot-available" aria-hidden="true"></i><b class="legend-symbol" aria-hidden="true">○</b> Tersedia</span>
                        <span><i class="dot dot-selected" aria-hidden="true"></i><b class="legend-symbol" aria-hidden="true">✓</b> Dipilih</span>
                        <span><i class="dot dot-taken" aria-hidden="true"></i><b class="legend-symbol" aria-hidden="true">×</b> Dikunci/dibooked</span>
                    </div>
                    <p class="seat-map-note muted small">Denah ilustratif; posisi kursi mengikuti konfigurasi armada.</p>

                    @if ($seatCount === 0)
                        <div class="alert" role="status">Semua kursi pada perjalanan ini sudah tidak tersedia.</div>
                    @else
                        @php
                            $seatTracks = $aisleAfter > 0
                                ? 'repeat('.$aisleAfter.', minmax(44px, 58px)) 22px repeat('.max(1, $seatColumns - $aisleAfter).', minmax(44px, 58px))'
                                : 'repeat('.$seatColumns.', minmax(44px, 58px))';
                        @endphp
                        <div class="bus-map" role="group" aria-labelledby="seat-map-title"
                             style="--seat-tracks: {{ $seatTracks }}">
                            <div class="bus-front" aria-hidden="true">Depan bus</div>

                            @foreach ($seatMap->decks() as $deck)
                                <section class="seat-deck" aria-labelledby="deck-{{ $deck['number'] }}">
                                    <h3 class="seat-deck-label" id="deck-{{ $deck['number'] }}">{{ $deck['label'] }}</h3>

                                    @foreach ($deck['rows'] as $row)
                                        <div class="seat-row" role="group" aria-label="Baris {{ $row['number'] }}">
                                            @foreach ($row['seats'] as $item)
                                                @php
                                                    $label = (string) $item['label'];
                                                    $price = (int) $item['price'];
                                                    $checked = in_array($label, old('seats', []), true);
                                                    $description = 'Kursi '.$label.', '.$item['state_label']
                                                        .', Rp '.number_format($price, 0, ',', '.')
                                                        .', baris '.$item['row'].', kolom '.$item['col'];
                                                @endphp
                                                <label class="seat seat-{{ $item['selectable'] ? 'available' : 'taken' }} seat-{{ $item['type'] }}"
                                                       data-row="{{ $item['row'] }}" data-col="{{ $item['col'] }}"
                                                       data-deck="{{ $item['deck'] }}" data-type="{{ $item['type'] }}"
                                                       title="{{ $label }} &middot; {{ $item['state_label'] }} &middot; Rp {{ number_format($price, 0, ',', '.') }}">
                                                    <input type="checkbox" name="seats[]" value="{{ $label }}"
                                                           data-price="{{ $price }}" data-label="{{ $label }}"
                                                           data-status="{{ $item['status'] }}"
                                                           aria-label="{{ $description }}"
                                                           @checked($checked) @disabled(! $item['selectable'])>
                                                    <span class="seat-symbol" aria-hidden="true">{{ $item['symbol'] }}</span>
                                                    <span class="seat-label" aria-hidden="true">{{ $label }}</span>
                                                </label>

                                                @if ($aisleAfter > 0 && (int) $item['col'] === $aisleAfter)
                                                    <span class="bus-aisle" aria-hidden="true"></span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endforeach
                                </section>
                            @endforeach

                            <div class="bus-rear" aria-hidden="true">Belakang bus</div>
                        </div>
                    @endif
                </div>

                <div class="card">
                    <h2>Data penumpang</h2>
                    <p class="muted small">Isi sesuai identitas yang akan ditunjukkan saat naik.</p>

                    @for ($i = 0; $i < $passengerForms; $i++)
                        <fieldset class="passenger-card">
                            <legend>Penumpang {{ $i + 1 }}</legend>
                            <div class="form-grid">
                                <label class="field">
                                    Nama lengkap
                                    <input type="text" name="passenger[{{ $i }}][name]"
                                           value="{{ old('passenger.'.$i.'.name') }}" maxlength="120" required>
                                </label>
                                <label class="field">
                                    Umur <span class="muted">(opsional)</span>
                                    <input type="number" name="passenger[{{ $i }}][age]"
                                           value="{{ old('passenger.'.$i.'.age') }}" min="1" max="120">
                                </label>
                                <label class="field">
                                    Jenis kelamin <span class="muted">(opsional)</span>
                                    <select name="passenger[{{ $i }}][gender]">
                                        <option value="">Pilih</option>
                                        <option value="male" @selected(old('passenger.'.$i.'.gender') === 'male')>Laki-laki</option>
                                        <option value="female" @selected(old('passenger.'.$i.'.gender') === 'female')>Perempuan</option>
                                    </select>
                                </label>
                            </div>
                        </fieldset>
                    @endfor
                </div>

                <div class="card">
                    <h2>Titik naik &amp; turun</h2>
                    <div class="form-grid">
                        <label class="field">
                            Naik di
                            <select name="boarding_stop_id" required>
                                <option value="">Pilih titik naik</option>
                                @foreach ($boardingStops as $stop)
                                    <option value="{{ $stop->bus_stop_id }}"
                                        @selected((string) old('boarding_stop_id') === (string) $stop->bus_stop_id)>
                                        {{ \App\Support\Schedule::stopName($stop) }} ·
                                        {{ \App\Support\Schedule::timeAt($trip, $stop)->format('H:i') }}
                                        ({{ \App\Support\Schedule::cityName($stop) }})
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field">
                            Turun di
                            <select name="dropping_stop_id" required>
                                <option value="">Pilih titik turun</option>
                                @foreach ($droppingStops as $stop)
                                    <option value="{{ $stop->bus_stop_id }}"
                                        @selected((string) old('dropping_stop_id') === (string) $stop->bus_stop_id)>
                                        {{ \App\Support\Schedule::stopName($stop) }} ·
                                        {{ \App\Support\Schedule::timeAt($trip, $stop)->format('H:i') }}
                                        ({{ \App\Support\Schedule::cityName($stop) }})
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                <div class="card">
                    <h2>Data pemesan</h2>
                    <p class="muted small">Kode booking dan e-tiket akan dikirim ke email ini.</p>
                    <div class="form-grid">
                        <label class="field">
                            Nama pemesan
                            <input type="text" name="contact_name" value="{{ old('contact_name') }}" maxlength="120" required>
                        </label>
                        <label class="field">
                            Email
                            <input type="email" name="contact_email" value="{{ old('contact_email') }}" maxlength="150" required>
                        </label>
                        <label class="field">
                            Nomor telepon
                            <input type="tel" name="contact_phone" value="{{ old('contact_phone') }}" maxlength="30" required>
                        </label>
                    </div>
                </div>
            </div>

            <aside class="sticky-summary">
                <div class="card">
                    <h2>Ringkasan pesanan</h2>
                    <div class="summary-seats" id="selected-seats" aria-live="polite">
                        <span class="muted small">Belum ada kursi dipilih.</span>
                    </div>
                    <dl class="info-list">
                        <dt>Operator</dt><dd>{{ $trip->bus->operator->name }}</dd>
                        <dt>Rute</dt><dd>{{ $trip->route->origin->name }} &rarr; {{ $trip->route->destination->name }}</dd>
                        <dt>Penumpang</dt><dd>{{ $passengerCount }} orang</dd>
                    </dl>
                    <div class="summary-total">
                        <span>Total</span>
                        <strong id="total-amount">Rp {{ number_format($estimate, 0, ',', '.') }}</strong>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit" @disabled($seatCount === 0)>
                        Lanjut ke pembayaran
                    </button>
                    <p class="muted small">Kursi dikunci 15 menit setelah Anda melanjutkan.</p>
                </div>
            </aside>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('booking-form');
        if (!form) return;

        const checkboxes = Array.from(form.querySelectorAll('input[name="seats[]"]:not(:disabled)'));
        const counter = document.getElementById('seat-counter');
        const list = document.getElementById('selected-seats');
        const total = document.getElementById('total-amount');
        const limit = {{ (int) $limit }};

        const money = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
        const warn = (message) => {
            if (window.TiketkuToast) {
                window.TiketkuToast.show(message, 'warning');
            }
        };

        const refresh = () => {
            const selected = checkboxes.filter((box) => box.checked);

            counter.textContent = selected.length + ' dari ' + limit + ' kursi dipilih';
            list.replaceChildren();

            if (selected.length === 0) {
                const empty = document.createElement('span');
                empty.className = 'muted small';
                empty.textContent = 'Belum ada kursi dipilih.';
                list.appendChild(empty);
            } else {
                selected.forEach((box) => {
                    const badge = document.createElement('span');
                    badge.className = 'badge';
                    badge.textContent = box.dataset.label + ' · ' + money(Number(box.dataset.price));
                    list.appendChild(badge);
                });
            }

            total.textContent = money(selected.reduce((sum, box) => sum + Number(box.dataset.price), 0));

            checkboxes.forEach((box) => {
                const limited = !box.checked && selected.length >= limit;
                box.setAttribute('aria-disabled', limited ? 'true' : 'false');
                box.closest('.seat')?.classList.toggle('is-limited', limited);
            });
        };

        checkboxes.filter((box) => box.checked).slice(limit).forEach((box) => { box.checked = false; });

        checkboxes.forEach((box) => {
            box.setAttribute('aria-disabled', 'false');
            box.addEventListener('change', () => {
                const selected = checkboxes.filter((item) => item.checked);

                if (selected.length > limit) {
                    box.checked = false;
                    warn('Maksimal ' + limit + ' kursi per pesanan.');
                }

                refresh();
            });
        });

        refresh();
    })();
</script>
@endpush
