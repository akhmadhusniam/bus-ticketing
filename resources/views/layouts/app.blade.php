<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand">{{ config('app.name') }}</a>
            <nav>
                <a href="{{ route('home') }}">Beranda</a>
                <a href="{{ route('home') }}#cari">Cari Tiket</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">&copy; {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</div>
    </footer>

    @stack('scripts')
</body>
</html>
