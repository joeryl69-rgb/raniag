<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ config('raniag.name') }} — Incident reporting for {{ config('raniag.organization') }}">

    <title>@yield('title', config('raniag.name')) — {{ config('raniag.organization') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
    @stack('styles')
    <link href="{{ asset('css/public.css') }}" rel="stylesheet">
    <style>
        #global-loading-overlay { position: fixed; inset: 0; z-index: 2000; background-color: rgba(15, 23, 42, 0.65); display: flex; align-items: center; justify-content: center; padding: 1rem; transition: opacity 0.2s ease; }
        #global-loading-overlay.d-none { display: none !important; }
        #global-loading-overlay .spinner-border { width: 3rem; height: 3rem; }
        #global-loading-overlay .loading-text { margin-top: 1rem; color: #f8fafc; font-weight: 600; }
    </style>
</head>
<body class="raniag-public d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark raniag-navbar shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('public.home') }}">
                <span class="raniag-brand-icon"><i class="bi bi-shield-check"></i></span>
                {{ config('raniag.name') }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav"
                    aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.home') ? 'active' : '' }}"
                           href="{{ route('public.home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.report.*') ? 'active' : '' }}"
                           href="{{ route('public.report.create') }}">Report Incident</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.track*') ? 'active' : '' }}"
                           href="{{ route('public.track') }}">Track Report</a>
                    </li>
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Staff Login</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="flex-grow-1 py-4 py-lg-5">
        @if (session('success'))
            <div class="container mb-4">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="raniag-footer py-4 mt-auto">
        <div class="container text-center text-white-50 small">
            <p class="mb-1">&copy; {{ date('Y') }} {{ config('raniag.organization') }} — {{ config('raniag.name') }}</p>
            <p class="mb-0">Incident Reporting and Analytics System</p>
        </div>
    </footer>

    <div id="global-loading-overlay" class="d-none">
        <div class="text-center">
            <div class="spinner-border text-white" role="status" aria-hidden="true"></div>
            <div class="loading-text">Processing, please wait...</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    function showLoadingOverlay(message = 'Processing, please wait...') {
        const overlay = document.getElementById('global-loading-overlay');
        if (!overlay) return;
        const label = overlay.querySelector('.loading-text');
        if (label) { label.textContent = message; }
        overlay.classList.remove('d-none');
    }
    function hideLoadingOverlay() {
        document.getElementById('global-loading-overlay')?.classList.add('d-none');
    }
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('SW Registered', reg))
                .catch(err => console.error('SW Registration Failed', err));
        });
    }
    </script>
    @stack('scripts')
</body>
</html>
