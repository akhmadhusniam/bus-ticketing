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
    <div class="page-skeleton" data-page-skeleton aria-hidden="true">
        <div class="container skeleton-inner">
            <div class="skeleton skeleton-line skeleton-title"></div>
            <div class="skeleton skeleton-line skeleton-subtitle"></div>
            <div class="skeleton-grid">
                @for ($i = 0; $i < 3; $i++)
                    <div class="skeleton-card">
                        <div class="skeleton skeleton-thumb"></div>
                        <div class="skeleton skeleton-line skeleton-row-wide"></div>
                        <div class="skeleton skeleton-line skeleton-row-mid"></div>
                        <div class="skeleton skeleton-line skeleton-row-short"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
    <div class="toast-stack" data-toast-stack aria-live="polite" aria-relevant="additions removals"></div>

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

    <script>
        (function () {
            var icons = { success: '✓', error: '×', warning: '!', info: 'i' };
            var loaderDelay = 120;

            function showToast(message, type, duration) {
                if (!message) {
                    return;
                }

                var stack = document.querySelector('[data-toast-stack]');
                if (!stack) {
                    return;
                }

                var kind = icons[type] ? type : 'info';
                var toast = document.createElement('div');
                toast.className = 'toast toast-' + kind;
                toast.setAttribute('role', kind === 'error' || kind === 'warning' ? 'alert' : 'status');

                var icon = document.createElement('span');
                icon.className = 'toast-icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.textContent = icons[kind];

                var copy = document.createElement('span');
                copy.className = 'toast-message';
                copy.textContent = message;

                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'toast-close';
                close.setAttribute('aria-label', 'Tutup notifikasi');
                close.innerHTML = '&times;';

                toast.appendChild(icon);
                toast.appendChild(copy);
                toast.appendChild(close);
                stack.appendChild(toast);

                while (stack.children.length > 4) {
                    stack.removeChild(stack.firstElementChild);
                }

                window.requestAnimationFrame(function () {
                    toast.classList.add('is-visible');
                });

                var timer = null;

                function dismiss() {
                    window.clearTimeout(timer);
                    toast.classList.remove('is-visible');
                    window.setTimeout(function () {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 220);
                }

                function schedule(ms) {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(dismiss, ms);
                }

                schedule(duration || 4500);
                close.addEventListener('click', dismiss);
                toast.addEventListener('mouseenter', function () { window.clearTimeout(timer); });
                toast.addEventListener('mouseleave', function () { schedule(1200); });
                toast.addEventListener('focusin', function () { window.clearTimeout(timer); });
                toast.addEventListener('focusout', function () { schedule(1200); });
            }

            window.TiketkuToast = { show: showToast };

            function startLoading() {
                document.body.classList.add('is-loading');
                document.body.setAttribute('aria-busy', 'true');
                window.setTimeout(stopLoading, 8000);
            }

            function stopLoading() {
                document.body.classList.remove('is-loading');
                document.body.removeAttribute('aria-busy');
            }

            function init() {
                document.querySelectorAll('form:not([data-no-loader])').forEach(function (form) {
                    form.addEventListener('submit', function () {
                        window.setTimeout(startLoading, loaderDelay);
                    });
                });

                document.querySelectorAll('[data-toast-source]').forEach(function (node) {
                    var message = node.textContent.trim();
                    if (!message) {
                        return;
                    }
                    showToast(message, node.getAttribute('data-toast-source') || 'info');
                    node.hidden = true;
                });

                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) {
                        stopLoading();
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
