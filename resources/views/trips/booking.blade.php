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
        <div class="alert" role="alert">{{ session('error') }}</div>
    @endif

    <p><a href="{{ $backUrl }}">&larr; Kembali ke detail perjalanan</a></p>

    <ol class="steps" aria-label="Langkah pemesanan">
        <li class="is-active"><span>1</span> Pilih kursi &amp; data penumpang</li>
        <li><span>2</span> Pembayaran</li>
        <li><span>3</span> E-tiket</li>
    </ol>

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
                            <h2>Pilih kursi</h2>
                            <p class="muted small">Maksimal {{ $limit }} kursi per pesanan.</p>
                        </div>
                        <span class="badge" id="seat-counter">0 terpilih</span>
                    </div>

                    <div class="seat-legend" aria-hidden="true">
                        <span><i class="dot dot-available"></i> Tersedia</span>
                        <span><i class="dot dot-selected"></i> Dipilih</span>
                        <span><i class="dot dot-taken"></i> Dikunci/dibooked</span>
                    </div>

                    @if ($seatCount === 0)
                        <div class="alert" role="status">Semua kursi pada perjalanan ini sudah tidak tersedia.</div>
                    @else
                        @foreach ($seatRows as $row)
                            @php
                                $rowSeats = collect($row)->sortBy('col');
                            @endphp
                            <div class="seat-deck">
                                <div class="seat-deck-label">Dek {{ $rowSeats->first()['deck'] }}</div>
                                <div class="seat-row" style="--seat-cols: {{ max(1, (int) $seatColumns) }}">
                                    @foreach ($rowSeats as $item)
                                        @php
                                            $selectable = $item['seat'] && $item['seat']->status === 'available';
                                            $checked = in_array($item['label'], old('seats', []), true);
                                        @endphp
                                        <label class="seat seat-{{ $selectable ? 'available' : 'taken' }}"
                                               title="{{ $item['label'] }} · Rp {{ number_format($item['price'], 0, ',', '.') }}">
                                            <input type="checkbox" name="seats[]" value="{{ $item['label'] }}"
                                                   data-price="{{ $item['price'] }}" data-label="{{ $item['label'] }}"
                                                   @checked($checked) @disabled(! $selectable)>
                                            <span>{{ $item['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
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
                    <div class="summary-seats" id="selected-seats">
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

        const refresh = () => {
            const selected = checkboxes.filter((box) => box.checked);
            counter.textContent = selected.length + ' terpilih';
            list.innerHTML = selected.length
                ? selected.map((box) => '<span class="badge">' + box.dataset.label + '</span>').join(' ')
                : '<span class="muted small">Belum ada kursi dipilih.</span>';
            total.textContent = money(selected.reduce((sum, box) => sum + Number(box.dataset.price), 0));
            checkboxes.forEach((box) => {
                if (!box.checked) box.disabled = selected.length >= limit;
            });
        };

        checkboxes.forEach((box) => box.addEventListener('change', refresh));
        refresh();
    })();
</script>
@endpush
