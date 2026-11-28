@extends('layouts.app')

@section('title', 'Tiket Bus & Shuttle Online — Tiketku')
@section('meta_description', 'Bandingkan jadwal bus dan shuttle antar kota, lihat harga, fasilitas, dan titik naik turun. Pesan tiket online dengan voucher dan e-tiket instan.')

@php
    $mode = request()->query('mode') === 'shuttle' ? 'shuttle' : 'bus';
    $isShuttle = $mode === 'shuttle';
    $displayTimezone = env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
    $today = now($displayTimezone)->toDateString();
    $chosenDate = old('date', request('date', $today));
    $chosenReturn = old('return_date', request('return_date', ''));

    $promos = [
        ['title' => 'Diskon akhir pekan', 'detail' => 'Potong 20% untuk pergi Jumat–Minggu.', 'code' => 'WEEKEND20'],
        ['title' => 'Hemat pergi pulang', 'detail' => 'Diskon 15% untuk dua perjalanan.', 'code' => 'PULANG15'],
        ['title' => 'Khusus penumpang baru', 'detail' => 'Potongan Rp25.000 untuk pesanan pertama.', 'code' => 'BARU25'],
        ['title' => 'Promo shuttle', 'detail' => 'Harga khusus rute shuttle pilihan.', 'code' => 'SHUTTLE10'],
    ];

    $reviews = [
        ['name' => 'Nadia P.', 'route' => 'Jakarta → Bandung', 'quote' => 'Denah kursinya jelas, titik naiknya gampang ditemukan. E-tiket langsung masuk email.'],
        ['name' => 'Rizky A.', 'route' => 'Jakarta → Semarang', 'quote' => 'Bisa bandingin beberapa operator dalam satu halaman. Filter fasilitasnya membantu sekali.'],
        ['name' => 'Sinta W.', 'route' => 'Bandung → Jakarta', 'quote' => 'Cari tiket pulang pergi jadi simpel. Info jadwal dan harganya transparan sejak awal.'],
    ];

    $faqs = [
        ['q' => 'Bagaimana cara memesan tiket?', 'a' => 'Pilih asal, tujuan, tanggal, lalu bandingkan jadwal di halaman hasil. Setelah itu buka detail trip, pilih kursi, dan ikuti langkah pembayaran.'],
        ['q' => 'Apakah bisa mencari tiket pulang pergi?', 'a' => 'Bisa. Isi tanggal pulang di kolom opsional pada pencarian. Setelah memilih tiket pergi, gunakan tombol “Cari tiket pulang” di halaman hasil.'],
        ['q' => 'Di mana saya melihat e-tiket?', 'a' => 'Buka menu “Cek Tiket”, lalu masukkan kode booking dan email pemesan. E-tiket menampilkan kursi, jadwal, serta titik naik dan turun.'],
        ['q' => 'Apakah kode voucher bisa digabung?', 'a' => 'Satu pesanan hanya memakai satu kode voucher. Pilih kode yang memberikan potongan terbesar untuk perjalanan Anda.'],
        ['q' => 'Bagaimana jika jadwal berubah?', 'a' => 'Status perjalanan dan perubahan jadwal akan terlihat pada detail trip dan e-tiket. Simpan kode booking untuk keperluan bantuan.'],
    ];
@endphp

@section('content')
<section class="hero">
    <div class="container hero-container">
        <div class="hero-copy">
            <span class="hero-eyebrow">{{ $isShuttle ? 'Shuttle antar kota' : 'Bus antar kota' }}</span>
            <h1>{{ $isShuttle ? 'Naik shuttle, sampai tepat waktu.' : 'Perjalanan nyaman dimulai di sini.' }}</h1>
            <p class="lead">Bandingkan jadwal, harga, dan fasilitas dalam satu pencarian. Pilih kursi favorit, lalu terima e-tiket tanpa antre.</p>
        </div>

        <form id="cari" class="search-card hero-search" method="GET" action="{{ route('search') }}">
            <input type="hidden" name="mode" value="{{ $mode }}">

            @if ($errors->any())
                <div class="alert" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($cities->isEmpty())
                <div class="alert">Belum ada data kota. Impor <code>database/seed.sql</code> terlebih dahulu.</div>
            @endif

            <div class="hero-search-grid">
                <label class="search-field">
                    <span class="field-label">Dari</span>
                    <select name="origin" id="origin" required aria-label="Kota asal">
                        <option value="">Pilih kota asal</option>
                        @foreach ($cities->groupBy(fn ($city) => $city->country->name) as $country => $group)
                            <optgroup label="{{ $country }}">
                                @foreach ($group as $city)
                                    <option value="{{ $city->id }}" @selected(old('origin', request('to')) == $city->id)>{{ $city->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>

                <button type="button" id="swap" class="swap" aria-label="Tukar kota asal dan tujuan" title="Tukar asal dan tujuan">&#8644;</button>

                <label class="search-field">
                    <span class="field-label">Ke</span>
                    <select name="destination" id="destination" required aria-label="Kota tujuan">
                        <option value="">Pilih kota tujuan</option>
                        @foreach ($cities->groupBy(fn ($city) => $city->country->name) as $country => $group)
                            <optgroup label="{{ $country }}">
                                @foreach ($group as $city)
                                    <option value="{{ $city->id }}" @selected(old('destination', request('to')) == $city->id)>{{ $city->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>

                <label class="search-field">
                    <span class="field-label">Tanggal pergi</span>
                    <input type="date" name="date" id="departure-date" value="{{ $chosenDate }}" min="{{ $today }}" required>
                </label>

                <label class="search-field">
                    <span class="field-label">Tanggal pulang <small>(opsional)</small></span>
                    <input type="date" name="return_date" id="return-date" value="{{ $chosenReturn }}" min="{{ $chosenDate }}">
                </label>

                <label class="search-field search-field-narrow">
                    <span class="field-label">Penumpang</span>
                    <select name="passengers" aria-label="Jumlah penumpang">
                        @for ($n = 1; $n <= 6; $n++)
                            <option value="{{ $n }}" @selected(old('passengers', 1) == $n)>{{ $n }} orang</option>
                        @endfor
                    </select>
                </label>

                <button class="btn btn-search" type="submit">Cari {{ ucfirst($mode) }}</button>
            </div>

            <div class="search-footnote">
                <span>Waktu ditampilkan sesuai zona kota setempat ({{ $displayTimezone }}).</span>
                <span>Tanggal pulang boleh dikosongkan untuk perjalanan sekali jalan.</span>
            </div>
        </form>
    </div>
</section>

<section class="section promo-section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-kicker">Hemat lebih banyak</span>
                <h2>Promo dengan kode voucher</h2>
            </div>
            <p class="section-note">Salin kode, lalu masukkan saat pembayaran.</p>
        </div>
        <div class="promo-grid">
            @foreach ($promos as $promo)
                <article class="promo-card">
                    <span class="promo-tag">Voucher</span>
                    <h3>{{ $promo['title'] }}</h3>
                    <p>{{ $promo['detail'] }}</p>
                    <div class="voucher">
                        <code>{{ $promo['code'] }}</code>
                        <button type="button" class="copy-btn" data-copy="{{ $promo['code'] }}" aria-label="Salin kode {{ $promo['code'] }}" aria-live="polite">Salin</button>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="trust-band">
    <div class="container trust-grid">
        <div class="trust-item"><strong>4.8<span>/5</span></strong><span>rata-rata ulasan penumpang</span></div>
        <div class="trust-item"><strong>{{ $stats['cities'] }}+</strong><span>kota tujuan</span></div>
        <div class="trust-item"><strong>{{ $stats['operators'] }}</strong><span>operator terverifikasi</span></div>
        <div class="trust-item"><strong>{{ $stats['trips'] }}</strong><span>jadwal siap dipesan</span></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-kicker">Mitra perjalanan</span>
                <h2>Operator bus & shuttle pilihan</h2>
            </div>
            <p class="section-note">Armada terawat dengan titik naik yang jelas.</p>
        </div>
        <div class="operator-grid">
            @forelse ($operators as $operator)
                <article class="operator-card">
                    @if ($operator->logo_path)
                        <img class="operator-logo" src="{{ str_starts_with($operator->logo_path, 'http') ? $operator->logo_path : asset($operator->logo_path) }}" alt="Logo {{ $operator->name }}">
                    @else
                        <span class="operator-logo logo-tone-{{ $loop->index % 4 }}" aria-hidden="true">{{ strtoupper(mb_substr($operator->name, 0, 1)) }}</span>
                    @endif
                    <div class="operator-copy">
                        <strong>{{ $operator->name }}</strong>
                        <span>&#9733; {{ number_format((float) $operator->rating, 1) }} rating</span>
                    </div>
                </article>
            @empty
                <p class="muted">Operator belum tersedia.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="section section-soft">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-kicker">Mulai dari mana</span>
                <h2>Rute favorit penumpang</h2>
            </div>
            <p class="section-note">Klik rute untuk melihat jadwal dan halaman informasinya.</p>
        </div>
        <div class="favorite-grid">
            @if ($popular->isNotEmpty())
                <div class="favorite-block">
                    <h3>Kota populer</h3>
                    <div class="chips">
                        @foreach ($popular as $city)
                            <a class="chip" href="{{ route('home', ['to' => $city->id, 'mode' => $mode]) }}#cari">{{ $city->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($popularRoutes->isNotEmpty())
                <div class="favorite-block">
                    <h3>Rute populer</h3>
                    <div class="chips">
                        @foreach ($popularRoutes as $popularRoute)
                            <a class="chip" href="{{ route('route.landing', [
                                'origin' => $popularRoute->origin->slug,
                                'destination' => $popularRoute->destination->slug,
                            ]) }}">{{ $popularRoute->origin->name }} – {{ $popularRoute->destination->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-kicker">Cara kerja</span>
                <h2>Tiga langkah sampai berangkat</h2>
            </div>
            <p class="section-note">Tanpa langkah tersembunyi.</p>
        </div>
        <div class="steps steps-three">
            <div class="step"><span class="num">1</span><h3>Cari & bandingkan</h3><p>Isi rute dan tanggal, lalu filter jadwal, harga, dan fasilitas.</p></div>
            <div class="step"><span class="num">2</span><h3>Pilih kursi</h3><p>Lihat denah kursi, titik naik, dan titik turun sebelum membayar.</p></div>
            <div class="step"><span class="num">3</span><h3>Simpan e-tiket</h3><p>Cek tiket kapan saja memakai kode booking dan email pemesan.</p></div>
        </div>
    </div>
</section>

<section class="section section-soft">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-kicker">Dari penumpang</span>
                <h2>Kata mereka yang sudah berangkat</h2>
            </div>
            <p class="section-note">Ulasan dari perjalanan nyata.</p>
        </div>
        <div class="review-grid">
            @foreach ($reviews as $review)
                <article class="review-card">
                    <div class="review-stars" aria-label="Rating 5 dari 5">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <p>“{{ $review['quote'] }}”</p>
                    <div class="review-author">
                        <span class="avatar" aria-hidden="true">{{ strtoupper(mb_substr($review['name'], 0, 1)) }}</span>
                        <span><strong>{{ $review['name'] }}</strong><small>{{ $review['route'] }}</small></span>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="section faq-section">
    <div class="container faq-layout">
        <div class="faq-intro">
            <span class="section-kicker">Butuh bantuan?</span>
            <h2>Pertanyaan umum</h2>
            <p class="muted">Jawaban singkat sebelum Anda memesan.</p>
        </div>
        <div class="faq-list">
            @foreach ($faqs as $faq)
                <details class="faq-item">
                    <summary>{{ $faq['q'] }}</summary>
                    <p>{{ $faq['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (function () {
        var from = document.getElementById('origin');
        var to = document.getElementById('destination');
        var swap = document.getElementById('swap');
        if (swap) {
            swap.addEventListener('click', function () {
                var current = from.value;
                from.value = to.value;
                to.value = current;
            });
        }

        var depart = document.getElementById('departure-date');
        var back = document.getElementById('return-date');
        if (depart && back) {
            depart.addEventListener('change', function () {
                back.min = depart.value;
                if (back.value && back.value < depart.value) {
                    back.value = '';
                }
            });
        }

        document.querySelectorAll('.copy-btn').forEach(function (button) {
            button.addEventListener('click', async function () {
                var code = button.getAttribute('data-copy');
                try {
                    await navigator.clipboard.writeText(code);
                } catch (error) {
                    var helper = document.createElement('textarea');
                    helper.value = code;
                    helper.style.position = 'fixed';
                    helper.style.opacity = '0';
                    document.body.appendChild(helper);
                    helper.select();
                    document.execCommand('copy');
                    document.body.removeChild(helper);
                }
                var original = button.textContent;
                button.textContent = 'Tersalin';
                button.classList.add('copied');
                window.setTimeout(function () {
                    button.textContent = original;
                    button.classList.remove('copied');
                }, 1600);
            });
        });

        var faqItems = Array.prototype.slice.call(document.querySelectorAll('.faq-item'));
        faqItems.forEach(function (item) {
            item.addEventListener('toggle', function () {
                if (!item.open) {
                    return;
                }
                faqItems.forEach(function (other) {
                    if (other !== item) {
                        other.open = false;
                    }
                });
            });
        });
    })();
</script>
@endpush
