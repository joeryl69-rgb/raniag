<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Staff Portal') — RANIAG · MDRRMO Pamplona</title>

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" href="{{ asset('vendor/bootstrap-icons/fonts/bootstrap-icons.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/public.css') }}?v={{ @filemtime(public_path('css/public.css')) }}" rel="stylesheet">
    <link rel="manifest" href="/manifest.json?v=8">
    <meta name="theme-color" content="#0e4a6b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="/images/icons/raniag-master.svg?v=8">
    <link rel="alternate icon" type="image/png" href="/favicon.png?v=8">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png?v=8">
    @stack('styles')

    <style>
        html {
            scrollbar-gutter: stable;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: var(--raniag-surface);
            overflow-x: hidden;
        }
        
        #wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        #sidebar-wrapper {
            width: 260px;
            background-color: var(--raniag-sidebar);
            color: #a9c1cf;
            flex-shrink: 0;
            transition: margin-left 0.25s ease;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.06);
            overflow: hidden;
        }

        #sidebar-wrapper .sidebar-nav-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.25) transparent;
            padding-bottom: max(1rem, env(safe-area-inset-bottom, 0px));
        }

        #sidebar-wrapper .sidebar-nav-scroll::-webkit-scrollbar {
            width: 6px;
        }

        #sidebar-wrapper .sidebar-nav-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(255,255,255,0.25);
            border-radius: 3px;
        }

        #sidebar-wrapper .sidebar-brand,
        #sidebar-wrapper .sidebar-profile {
            flex-shrink: 0;
        }

        #sidebar-wrapper .sidebar-brand {
            padding: 1.5rem 1.25rem;
            background: linear-gradient(135deg, var(--raniag-primary-dark), var(--raniag-primary));
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        #sidebar-wrapper .sidebar-profile {
            padding: 1.25rem;
            background-color: rgba(255,255,255,0.04);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        #sidebar-wrapper .nav-link {
            color: #a9c1cf;
            padding: 0.8rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
            min-height: 44px;
        }

        #sidebar-wrapper .nav-link:hover {
            color: #fff;
            background-color: var(--raniag-sidebar-active);
        }

        #sidebar-wrapper .nav-link.active {
            color: #fff;
            background-color: var(--raniag-sidebar-active);
            border-left-color: var(--raniag-accent);
            font-weight: 600;
        }

        #sidebar-wrapper .nav-section-label {
            padding: 1rem 1.25rem 0.35rem;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6f8ea0;
        }

        #sidebar-wrapper .nav-item + .nav-section-label {
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: 0.4rem;
        }

        #page-content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
 
        #global-loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background-color: rgba(15, 23, 42, 0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            transition: opacity 0.2s ease;
        }
 
        #global-loading-overlay.d-none {
            display: none !important;
        }

        /* Robust scroll lock (see showLoadingOverlay/hideLoadingOverlay below):
           overflow:hidden on <body> alone doesn't stop iOS rubber-band
           scrolling, so the page could still be dragged behind the overlay. */
        body.rg-scroll-locked {
            overflow: hidden;
            position: fixed;
            left: 0;
            right: 0;
            width: 100%;
        }
 
        #global-loading-overlay .loading-text {
            margin-top: 1rem;
            color: #f8fafc;
            font-weight: 600;
        }
        #global-loading-overlay .rg-mark { width: 56px; height: 56px; margin: 0 auto; position: relative; }
        #global-loading-overlay .rg-mark img {
            width: 100%; height: 100%; border-radius: 14px;
            animation: rg-pulse 1.15s ease-in-out infinite;
        }
        #global-loading-overlay .rg-mark::after {
            content: ""; position: absolute; inset: -8px; border-radius: 18px;
            border: 2px solid #93c5fd; opacity: 0;
            animation: rg-ring 1.15s ease-out infinite;
        }
        @keyframes rg-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(.9); } }
        @keyframes rg-ring { 0% { transform: scale(.85); opacity: .6; } 100% { transform: scale(1.35); opacity: 0; } }
        @media (prefers-reduced-motion: reduce) {
            #global-loading-overlay .rg-mark img, #global-loading-overlay .rg-mark::after { animation: none; }
        }
 
        .leaflet-control-attribution {
            display: none !important;
        }

        .navbar-top {
            background-color: #fff;
            border-bottom: 1px solid var(--raniag-border);
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1015;
            min-height: 60px;
            /* Bootstrap's .navbar defaults to flex-wrap:wrap. Combined with
               a long page title, that let the right-hand group (date +
               bell) wrap onto its own row — and once alone on that row,
               justify-content:space-between has nothing to space apart, so
               it collapsed to the left, landing the bell underneath the
               hamburger button instead of staying on the right. */
            flex-wrap: nowrap;
        }

        .navbar-top .navbar-top-title {
            min-width: 0;
        }

        .navbar-top .navbar-top-title h4 {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 575.98px) {
            .navbar-top {
                padding: 0.75rem 1rem;
            }
        }

        /* Make sidebar fixed on desktop only to preserve mobile responsiveness */
        @media (min-width: 992px) {
            #sidebar-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                height: 100dvh;
                z-index: 1040;
            }

            /* Shift page content to account for fixed sidebar on desktop */
            #page-content-wrapper {
                margin-left: 260px;
            }

            #page-content-wrapper .container-fluid {
                padding-top: 1rem;
            }
        }

        /* Mobile Responsive Sidebar */
        @media (max-width: 991.98px) {
            #sidebar-wrapper {
                margin-left: -260px;
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                height: 100dvh;
                z-index: 1040;
            }
            #wrapper.toggled #sidebar-wrapper {
                margin-left: 0;
            }
            body.sidebar-open {
                overflow: hidden;
                touch-action: none;
            }
            body.sidebar-open #page-content-wrapper {
                touch-action: none;
            }
            #sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background-color: rgba(0,0,0,0.4);
                z-index: 1030;
            }
            #wrapper.toggled #sidebar-overlay {
                display: block;
            }
        }
        
        .fs-8 {
            font-size: 0.75rem;
        }

        @font-face {
            font-family: bootstrap-icons;
            font-display: swap;
            src: url("{{ asset('vendor/bootstrap-icons/fonts/bootstrap-icons.woff2') }}") format("woff2");
        }

        [data-live-refresh-target] [data-rg-reveal] { opacity: 1; transform: none; }
    </style>
</head>
<body>
    @if(auth()->user()->isAgency() || auth()->user()->isPersonnel())
        <x-help-fab :href="route('agency.support.create')" />
    @endif
    <div id="wrapper">
        <!-- Overlay for mobile toggle -->
        <div id="sidebar-overlay" role="presentation" aria-hidden="true"></div>

        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-brand">
                <a class="text-white text-decoration-none fw-bold d-flex align-items-center gap-2 fs-5" href="{{ route('dashboard') }}">
                    <span class="bg-primary text-white d-inline-flex align-items-center justify-content-center rounded overflow-hidden" style="width: 2rem; height: 2rem;">
                        <img src="/images/icons/raniag-master.svg" alt="RANIAG" class="w-100 h-100" style="object-fit:contain;">
                    </span>
                    <span class="d-flex flex-column lh-sm">
                        <span>RANIAG</span>
                        <span class="fw-normal text-white-50" style="font-size: 0.62rem; letter-spacing: 0.04em;">MDRRMO PAMPLONA</span>
                    </span>
                </a>
            </div>

            <!-- Profile Widget -->
            <div class="sidebar-profile d-flex align-items-center gap-3">
                <a href="{{ route('profile.edit') }}" class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0 text-decoration-none fw-bold" style="width: 2.5rem; height: 2.5rem;" title="Edit profile">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-100 h-100" style="object-fit:cover;">
                    @else
                        {{ auth()->user()->initials }}
                    @endif
                </a>
                <div class="text-truncate">
                    <div class="fw-bold text-white small text-truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                    @if (auth()->user()->isAdministrator())
                        <span class="badge bg-danger fs-8 fw-normal">Administrator</span>
                    @elseif (auth()->user()->isPersonnel())
                        <span class="badge fs-8 fw-normal" style="background-color: var(--raniag-accent); color: #fff;">Personnel</span>
                    @else
                        <span class="badge fs-8 fw-normal" style="background-color: var(--raniag-accent); color: #fff;">{{ auth()->user()->agency->code ?? 'Agency' }}</span>
                    @endif
                </div>
            </div>

            <!-- Centralized Navigation Component -->
            <x-sidebar-nav />
        </div>

        <!-- Content Area -->
        <div id="page-content-wrapper">
            <!-- Top bar -->
            <nav class="navbar navbar-top d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3 navbar-top-title">
                    <button class="btn btn-outline-dark d-lg-none flex-shrink-0" type="button" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    @if (isset($header))
                        <h4 class="fw-bold mb-0 text-dark fs-5">{{ $header }}</h4>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                    <div class="text-muted small d-none d-sm-block">
                        <i class="bi bi-calendar3 me-1"></i>{{ date('l, M d, Y') }}
                    </div>
                    <x-notification-bell />
                </div>
            </nav>

            <!-- Main Panel Content -->
            <div class="container-fluid p-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')

            </div>
        </div>
    </div>

    </div>
 
    <div id="global-loading-overlay" class="d-none">
        <div class="text-center">
            <div class="rg-mark" aria-hidden="true"><img src="/images/icons/raniag-master.svg" alt=""></div>
            <div class="loading-text">Processing, please wait...</div>
        </div>
    </div>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/live-refresh.js') }}?v={{ @filemtime(public_path('js/live-refresh.js')) }}"></script>
    <script src="{{ asset('js/filter-bar.js') }}?v={{ @filemtime(public_path('js/filter-bar.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script>
        function toggleSidebar(force) {
            const wrapper = document.getElementById('wrapper');
            if (!wrapper) return;
            const open = typeof force === 'boolean' ? force : !wrapper.classList.contains('toggled');
            wrapper.classList.toggle('toggled', open);
            document.body.classList.toggle('sidebar-open', open);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('sidebar-overlay');
            if (overlay) {
                overlay.addEventListener('click', function () { toggleSidebar(false); });
            }

            document.querySelectorAll('#sidebar-wrapper .nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 991.98px)').matches) {
                        toggleSidebar(false);
                    }
                });
            });
        });
 
        let staffLockedScrollY = 0;
        function showLoadingOverlay(message = 'Processing, please wait...') {
            const overlay = document.getElementById('global-loading-overlay');
            if (!overlay) return;
            const label = overlay.querySelector('.loading-text');
            if (label) {
                label.textContent = message;
            }
            overlay.classList.remove('d-none');
            staffLockedScrollY = window.scrollY || window.pageYOffset || 0;
            document.body.classList.add('rg-scroll-locked');
            document.body.style.top = (-staffLockedScrollY) + 'px';
        }
 
        function hideLoadingOverlay() {
            const overlay = document.getElementById('global-loading-overlay');
            if (overlay) {
                overlay.classList.add('d-none');
            }
            document.body.classList.remove('rg-scroll-locked');
            document.body.style.top = '';
            window.scrollTo(0, staffLockedScrollY);
        }

        function setButtonLoading(button, message = 'Processing, please wait...') {
            if (!button) return;
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }
            button.disabled = true;
            button.classList.add('disabled');
            const spinner = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';
            button.innerHTML = spinner + (message || 'Processing...');
        }

        function resetButtonLoading(button) {
            if (!button) return;
            button.disabled = false;
            button.classList.remove('disabled');
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
 
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function (form) {
                let lastClickedSubmit = null;
                form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                    btn.addEventListener('click', function () { lastClickedSubmit = btn; });
                });

                form.addEventListener('submit', function () {
                    if (this.classList.contains('no-loading')) return;
                    const submitButton = lastClickedSubmit || this.querySelector('button[type="submit"]');
                    const message = submitButton?.dataset.loadingMessage || this.dataset.loadingMessage || 'Processing, please wait...';
                    showLoadingOverlay(message);
                    if (submitButton) {
                        setButtonLoading(submitButton, message);
                    }

                    // For forms that trigger a file download (e.g. PDF export), the browser
                    // never fires a new 'load' event, so poll for a cookie set by the server
                    // once the download response has actually started.
                    const tokenInput = this.querySelector('#download_token');
                    if (tokenInput) {
                        const token = Date.now().toString();
                        tokenInput.value = token;
                        const check = setInterval(function () {
                            if (document.cookie.includes('download_token=' + token)) {
                                clearInterval(check);
                                hideLoadingOverlay();
                                resetButtonLoading(submitButton);
                                document.cookie = 'download_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                            }
                        }, 300);
                    }
                });
            });

            document.querySelectorAll('[data-loading-link]').forEach(function (link) {
                link.addEventListener('click', function () {
                    const href = this.getAttribute('href');
                    if (!href || href.startsWith('#')) return;
                    showLoadingOverlay(this.dataset.loadingMessage || 'Loading, please wait...');
                });
            });

            // Hide overlay as soon as DOM is ready — don't wait for every image/font
            // (window.load), which made icons and page content feel delayed.
            hideLoadingOverlay();

            window.addEventListener('pageshow', function () {
                hideLoadingOverlay();
            });
        });
    </script>
    <x-lightbox />
    @stack('scripts')
</body>
</html>