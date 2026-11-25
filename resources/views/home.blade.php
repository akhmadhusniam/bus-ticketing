@extends('layouts.app')

@section('title', 'Pesan Tiket Bus Online')

@section('content')
<section class="hero">
    <div class="container">
        <h1>Pesan tiket bus ke mana saja, tanpa ribet.</h1>
        <p class="lead">Pilih tujuan, lihat denah kursi, pilih kursi yang nyaman, dan pesan tiket.</p>

        <form id="cari" class="search-card" method="GET" action="{{ route('search') }}">
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

            <div class="search-grid">
                <label class="field">
                    <span>Dari</span>
                    <select name="origin" id="origin" required>
                        <option value="">Pilih kota asal</option>
                        @foreach ($cities->groupBy(fn ($c) => $c->country->name) as $country => $group)
                            <optgroup label="{{ $country }}">
                                @foreach ($group as $city)
                                    <option value="{{ $city->id }}" @selected(old('origin') == $city->id)>{{ $city->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>

                <button type="button" id="swap" class="swap" aria-label="Tukar kota asal dan tujuan">&#8644;</button>

                <label class="field">
                    <span>Ke</span>
                    <select name="destination" id="destination" required>
                        <option value="">Pilih kota tujuan</option>
                        @foreach ($cities->groupBy(fn ($c) => $c->country->name) as $country => $group)
                            <optgroup label="{{ $country }}">
                                @foreach ($group as $city)
                                    <option value="{{ $city->id }}" @selected(old('destination', request('to')) == $city->id)>{{ $city->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Tanggal berangkat</span>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                </label>

                <label class="field">
                    <span>Penumpang</span>
                    <select name="passengers">
                        @for ($n = 1; $n <= 6; $n++)
                            <option value="{{ $n }}" @selected(old('passengers', 1) == $n)>{{ $n }} orang</option>
                        @endfor
                    </select>
                </label>
            </div>

            <button class="btn" type="submit">Cari Bus</button>
        </form>
    </div>
</section>

@if ($popular->isNotEmpty())
<section class="section">
    <div class="container">
        <h2>Tujuan populer</h2>
        <div class="chips">
            @foreach ($popular as $city)
                <a class="chip" href="{{ route('home', ['to' => $city->id]) }}#cari">{{ $city->name }}</a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section alt">
    <div class="container">
        <h2>Cukup empat langkah</h2>
        <div class="steps">
            <div class="step"><span class="num">1</span><h3>Pilih tujuan</h3><p>Tentukan kota asal, tujuan, dan tanggal berangkat.</p></div>
            <div class="step"><span class="num">2</span><h3>Lihat denah kursi</h3><p>Bandingkan jadwal, harga, dan fasilitas bus.</p></div>
            <div class="step"><span class="num">3</span><h3>Pilih kursi</h3><p>Ambil kursi paling nyaman langsung dari denah.</p></div>
            <div class="step"><span class="num">4</span><h3>Pesan tiket</h3><p>Bayar dan terima e-tiket di email atau WhatsApp.</p></div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.getElementById('swap').addEventListener('click', function () {
        var from = document.getElementById('origin');
        var to = document.getElementById('destination');
        var tmp = from.value;
        from.value = to.value;
        to.value = tmp;
    });
</script>
@endpush
