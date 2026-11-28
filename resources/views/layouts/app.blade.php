<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Pesan tiket bus online: cari jadwal, bandingkan harga, dan lihat titik naik turun dengan mudah.')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    @yield('structured_data')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @php($activeMode = request()->query('mode') === 'shuttle' ? 'shuttle' : 'bus')
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand">Tiket<span>ku</span></a>
            <div class="header-actions">
                <div class="mode-switch" role="group" aria-label="Switcher moda transportasi">
                    <a class="mode-option {{ $activeMode === 'bus' ? 'active' : '' }}"
                       href="{{ route('home', ['mode' => 'bus']) }}#cari"
                       @if ($activeMode === 'bus') aria-current="true" @endif>Bus</a>
                    <a class="mode-option {{ $activeMode === 'shuttle' ? 'active' : '' }}"
                       href="{{ route('home', ['mode' => 'shuttle']) }}#cari"
                       @if ($activeMode === 'shuttle') aria-current="true" @endif>Shuttle</a>
                </div>
                <nav>
                    <a href="{{ route('home') }}">Beranda</a>
                    <a href="{{ route('home', ['mode' => $activeMode]) }}#cari">Cari Tiket</a>
                    <a href="{{ route('ticket.lookup') }}">Cek Tiket</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <span>&copy; {{ date('Y') }} Tiketku. Semua hak dilindungi.</span>
            <span>Bus &amp; shuttle antar kota, satu tempat.</span>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
