@php
    // Per-user appearance now (App\Models\User::appearance()) — no more
    // shared global row, so one account's theme/dark-mode choice never
    // bleeds into another account's session. See AppearanceSettingController.
    $__raniagAppearance = (object) (auth()->user()?->appearance() ?? [
        'theme_key' => \App\Support\ThemePresets::DEFAULT_KEY,
        'dark_mode' => false,
        'follow_system' => false,
        'font_key' => \App\Support\ThemePresets::DEFAULT_FONT_KEY,
        'font_size' => \App\Support\ThemePresets::DEFAULT_FONT_SIZE,
    ]);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $__raniagAppearance->dark_mode && ! $__raniagAppearance->follow_system ? 'dark' : 'light' }}">
<head>
    <script>
        @if ($__raniagAppearance->follow_system)
            (function () {
                const colorMode = window.matchMedia('(prefers-color-scheme: dark)');
                const applySystemMode = () => document.documentElement.setAttribute('data-theme', colorMode.matches ? 'dark' : 'light');
                applySystemMode();
                colorMode.addEventListener('change', applySystemMode);
            })();
        @endif

        // Apply the saved desktop sidebar state before paint.
        (function () {
            const desktopViewport = window.matchMedia('(min-width: 992px)');
            const compactTouchLandscape = window.matchMedia('(orientation: landscape) and (max-height: 600px) and (pointer: coarse)');
            const syncSidebarState = () => {
                let collapsed = false;
                try {
                    collapsed = desktopViewport.matches && !compactTouchLandscape.matches
                        && localStorage.getItem('raniag-sidebar-collapsed') === '1';
                } catch (e) {}
                document.documentElement.classList.toggle('rg-sidebar-collapsed-init', collapsed);
            };
            syncSidebarState();
            desktopViewport.addEventListener('change', syncSidebarState);
            compactTouchLandscape.addEventListener('change', syncSidebarState);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-public-key" content="{{ config('services.webpush.public_key') }}">

    <title>@yield('title', 'Staff Portal') — RANIAG · MDRRMO Pamplona</title>

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="preload" href="{{ asset('vendor/bootstrap-icons/fonts/bootstrap-icons.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/public.css') }}?v={{ @filemtime(public_path('css/public.css')) }}" rel="stylesheet">
    <script src="{{ asset('js/map-scroll.js') }}?v={{ @filemtime(public_path('js/map-scroll.js')) }}"></script>
    <link href="{{ asset('css/workspace.css') }}?v={{ @filemtime(public_path('css/workspace.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/raniag-loader.css') }}?v={{ @filemtime(public_path('css/raniag-loader.css')) }}" rel="stylesheet">
    <link rel="manifest" href="/manifest.json?v=8">
    <meta name="theme-color" content="#0e4a6b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" type="image/svg+xml" href="/images/icons/raniag-master.svg?v=8">
    <link rel="alternate icon" type="image/png" href="/favicon.png?v=8">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.png?v=8">
    @stack('styles')

    {{-- Per-user theme (System Settings) — overrides the --raniag-*
         tokens defined in public.css :root, so every screen using those
         tokens (109 usages across public/admin/agency/personnel layouts)
         recolors together, no per-page exceptions. --}}
    <style>{!! \App\Support\ThemePresets::cssVariables($__raniagAppearance->theme_key, $__raniagAppearance->dark_mode && ! $__raniagAppearance->follow_system, $__raniagAppearance->font_key, $__raniagAppearance->font_size, $__raniagAppearance->follow_system) !!}</style>

    <style>
        html {
            scrollbar-gutter: stable;
            font-size: var(--raniag-font-root, 16px);
        }

        body {
            font-family: var(--raniag-font-family, 'Figtree', sans-serif);
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
            color: #b9cada;
            flex-shrink: 0;
            transition: margin-left 0.25s ease;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--raniag-border);
            overflow: visible;
        }

        #sidebar-wrapper .sidebar-nav-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.24) transparent;
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
            background: linear-gradient(145deg, var(--raniag-primary-dark), var(--raniag-primary));
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        #sidebar-wrapper .sidebar-profile {
            padding: 1.25rem;
            background-color: rgba(255,255,255,0.045);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        #sidebar-wrapper .nav-link {
            color: #b9cada;
            padding: 0.72rem 0.9rem;
            margin: 0.12rem 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border-left: 3px solid transparent;
            border-radius: 0.7rem;
            transition: color 0.18s ease, background-color 0.18s ease, transform 0.18s ease;
            min-height: 46px;
        }

        #sidebar-wrapper .nav-link > .bi {
            width: 1.25rem;
            flex: 0 0 1.25rem;
            color: #8ea8b8;
            text-align: center;
            font-size: 1.05rem;
        }

        #sidebar-wrapper .nav-link:hover {
            color: #fff;
            background-color: var(--raniag-sidebar-active);
            transform: translateX(2px);
        }

        #sidebar-wrapper .nav-link:hover > .bi,
        #sidebar-wrapper .nav-link.active > .bi {
            color: var(--raniag-accent);
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
            color: #849daf;
        }

        #sidebar-wrapper .nav-item + .nav-section-label {
            border-top: 1px solid rgba(255,255,255,0.09);
            margin-top: 0.55rem;
        }

        #sidebar-wrapper { border-right-color: rgba(255,255,255,0.08); }

        [data-theme="dark"] #sidebar-wrapper .sidebar-nav-scroll {
            scrollbar-color: rgba(255,255,255,0.25) transparent;
        }

        [data-theme="dark"] #sidebar-wrapper .sidebar-brand {
            border-bottom-color: rgba(255,255,255,0.08);
        }

        [data-theme="dark"] #sidebar-wrapper .sidebar-profile { background-color: rgba(255,255,255,0.045); }

        /* Collapsible "Agencies & Personnel" group (Coordination section) */
        #sidebar-wrapper .nav-caret {
            transition: transform 0.2s ease;
        }
        #sidebar-wrapper a[aria-expanded="true"] .nav-caret {
            transform: rotate(180deg);
        }
        #sidebar-wrapper .nav-submenu {
            padding-left: 0.5rem;
        }
        #sidebar-wrapper .nav-submenu .nav-link {
            padding-left: 2.5rem;
            min-height: 38px;
            font-size: 0.9rem;
        }

        #page-content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
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
                width: 260px;
                transition: width 0.2s ease;
            }

            /* Shift page content to account for fixed sidebar on desktop */
            #page-content-wrapper {
                margin-left: 260px;
                transition: margin-left 0.2s ease;
            }

            #page-content-wrapper .container-fluid {
                padding-top: 1rem;
            }

            /* Desktop collapse (icon rail) — independent of the mobile
               off-canvas "toggled" state, which only exists below 992px. */
            html.rg-sidebar-collapsed-init #sidebar-wrapper {
                width: 76px;
            }

            html.rg-sidebar-collapsed-init #page-content-wrapper {
                margin-left: 76px;
            }

            html.rg-sidebar-collapsed-init #sidebar-wrapper .sidebar-brand span:not(.rg-brand-mark),
            html.rg-sidebar-collapsed-init #sidebar-wrapper .nav-link span:not(.nav-caret):not(.sidebar-notif-count),
            html.rg-sidebar-collapsed-init #sidebar-wrapper .nav-section-label,
            html.rg-sidebar-collapsed-init #sidebar-wrapper .nav-caret,
            html.rg-sidebar-collapsed-init #sidebar-wrapper .nav-submenu {
                display: none !important;
            }

            html.rg-sidebar-collapsed-init #sidebar-wrapper .sidebar-brand {
                padding: 1.5rem 0;
                display: flex;
                justify-content: center;
            }

            html.rg-sidebar-collapsed-init #sidebar-wrapper .nav-link {
                justify-content: center;
                padding: 0.8rem 0;
            }

            html.rg-sidebar-collapsed-init #sidebar-wrapper [data-bs-toggle="collapse"] {
                pointer-events: none;
            }

            .rg-sidebar-collapse-btn {
                position: absolute;
                top: 1.35rem;
                right: -14px;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background-color: var(--raniag-primary);
                color: #fff;
                border: 2px solid var(--raniag-sidebar);
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.28);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1050;
                cursor: pointer;
                padding: 0;
            }

            html.rg-sidebar-collapsed-init .rg-sidebar-collapse-btn i {
                transform: rotate(180deg);
            }
        }

        @media (max-width: 991.98px) {
            .rg-sidebar-collapse-btn {
                display: none !important;
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

        @media (orientation: landscape) and (max-height: 600px) and (pointer: coarse) {
            #sidebar-wrapper {
                width: 260px;
                margin-left: -260px;
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                height: 100dvh;
                z-index: 1040;
            }
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
            #page-content-wrapper { width: 100%; min-width: 0; margin-left: 0; }
            #page-content-wrapper .container-fluid { min-width: 0; max-width: 100%; }
            .rg-sidebar-collapse-btn { display: none !important; }
            body.sidebar-open { overflow: hidden; touch-action: none; }
            body.sidebar-open #page-content-wrapper { touch-action: none; }
            #wrapper.toggled #sidebar-overlay { display: block; }
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
<body class="has-mobile-dock">
    @if(auth()->user()->isAgency() || auth()->user()->isPersonnel())
        <x-help-fab :href="route('agency.support.create')" />
    @endif
    <div id="wrapper">
        <!-- Overlay for mobile toggle -->
        <div id="sidebar-overlay" role="presentation" aria-hidden="true"></div>

        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <button type="button" class="rg-sidebar-collapse-btn d-none d-lg-flex" id="sidebarCollapseBtn" title="Collapse sidebar" aria-label="Collapse sidebar">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="sidebar-brand">
                <a class="text-white text-decoration-none fw-bold d-flex align-items-center gap-2 fs-5" href="{{ route('dashboard') }}">
                    <span class="rg-brand-mark bg-primary text-white d-inline-flex align-items-center justify-content-center rounded overflow-hidden flex-shrink-0" style="width: 2rem; height: 2rem;">
                        <img src="/images/icons/raniag-master.svg" alt="RANIAG" class="w-100 h-100" style="object-fit:contain;">
                    </span>
                    <span class="d-flex flex-column lh-sm">
                        <span>RANIAG</span>
                        <span class="fw-normal text-white-50" style="font-size: 0.62rem; letter-spacing: 0.04em;">MDRRMO PAMPLONA</span>
                    </span>
                </a>
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
                    <x-profile-menu />
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

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="fw-semibold mb-1">Could not save.</div>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')

            </div>
        </div>
    </div>

    </div>

    <x-mobile-dock />

    <x-loading-overlay />

    <script src="{{ asset('js/modal-root.js') }}?v={{ @filemtime(public_path('js/modal-root.js')) }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="{{ asset('js/live-refresh.js') }}?v={{ @filemtime(public_path('js/live-refresh.js')) }}"></script>
    <script src="{{ asset('js/filter-bar.js') }}?v={{ @filemtime(public_path('js/filter-bar.js')) }}"></script>
    <script src="{{ asset('js/filter-select.js') }}?v={{ @filemtime(public_path('js/filter-select.js')) }}" defer></script>
    <script src="{{ asset('js/filter-date-picker.js') }}?v={{ @filemtime(public_path('js/filter-date-picker.js')) }}" defer></script>
    <script src="{{ asset('js/notification-filters.js') }}?v={{ @filemtime(public_path('js/notification-filters.js')) }}"></script>
    <script src="{{ asset('js/feedback-disclosure.js') }}?v={{ @filemtime(public_path('js/feedback-disclosure.js')) }}"></script>
    <script src="{{ asset('js/table-view-mode.js') }}?v={{ @filemtime(public_path('js/table-view-mode.js')) }}"></script>
    <script src="{{ asset('js/notification-alert-sound.js') }}?v={{ @filemtime(public_path('js/notification-alert-sound.js')) }}"></script>
    <script>window.RANIAG_SERVICE_WORKER_URL = '/sw.js?v={{ @filemtime(public_path('sw.js')) }}';</script>
    <script src="{{ asset('js/push-notifications.js') }}?v={{ @filemtime(public_path('js/push-notifications.js')) }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" integrity="sha384-X9kJyAubVxnP0hcA+AMMs21U445qsnqhnUF8EBlEpP3a42Kh/JwWjlv2ZcvGfphb" crossorigin="anonymous"></script>
    <script>
        function toggleSidebar(force) {
            const wrapper = document.getElementById('wrapper');
            if (!wrapper) return;
            const open = typeof force === 'boolean' ? force : !wrapper.classList.contains('toggled');
            wrapper.classList.toggle('toggled', open);
            document.body.classList.toggle('sidebar-open', open);
        }

        // Desktop-only collapse (icon rail) — separate state from the mobile
        // off-canvas toggle above, so the two never interfere with each
        // other. Persisted so it stays collapsed across page loads.
        function setSidebarCollapsed(collapsed) {
            document.documentElement.classList.toggle('rg-sidebar-collapsed-init', collapsed);
            try { localStorage.setItem('raniag-sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
            const btn = document.getElementById('sidebarCollapseBtn');
            if (btn) btn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            syncSidebarTooltips(collapsed);
        }

        function syncSidebarTooltips(collapsed) {
            document.querySelectorAll('#sidebar-wrapper .nav-link').forEach(function (link) {
                const label = link.querySelector(':scope > span:not(.sidebar-notif-count)')?.textContent.trim();
                if (!label) return;
                link.setAttribute('aria-label', label);
                if (collapsed && window.bootstrap?.Tooltip) {
                    window.bootstrap.Tooltip.getOrCreateInstance(link, {
                        title: label,
                        placement: 'right',
                        container: 'body',
                    });
                } else {
                    window.bootstrap?.Tooltip?.getInstance(link)?.dispose();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            syncSidebarTooltips(document.documentElement.classList.contains('rg-sidebar-collapsed-init'));
            const collapseBtn = document.getElementById('sidebarCollapseBtn');
            if (collapseBtn) {
                collapseBtn.addEventListener('click', function () {
                    setSidebarCollapsed(!document.documentElement.classList.contains('rg-sidebar-collapsed-init'));
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('sidebar-overlay');
            if (overlay) {
                overlay.addEventListener('click', function () { toggleSidebar(false); });
            }

            document.querySelectorAll('#sidebar-wrapper .nav-link').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    const isCollapseToggle = link.getAttribute('data-bs-toggle') === 'collapse' || link.closest('[data-bs-toggle="collapse"]');
                    const href = link.getAttribute('href') || '';
                    const isHashOnlyLink = href === '#' || href.startsWith('#');

                    if (window.matchMedia('(max-width: 991.98px)').matches && !isCollapseToggle && !isHashOnlyLink) {
                        toggleSidebar(false);
                    }
                });
            });
        });
 
        let staffLockedScrollY = 0;
        function showLoadingOverlay(message = 'Processing, please wait...', options) {
            const overlay = document.getElementById('global-loading-overlay');
            if (!overlay) return;
            const label = overlay.querySelector('.loading-text');
            if (label) {
                label.textContent = message;
            }
            overlay.classList.remove('d-none');
            const lockScroll = !options || options.lockScroll !== false;
            if (lockScroll) {
                staffLockedScrollY = window.scrollY || window.pageYOffset || 0;
                document.body.classList.add('rg-scroll-locked');
                document.body.style.top = (-staffLockedScrollY) + 'px';
            }
            clearTimeout(window.__rgLoadingWatch);
            window.__rgLoadingWatch = setTimeout(function () {
                hideLoadingOverlay();
                document.querySelectorAll('form[data-rg-submitting]').forEach(function (form) {
                    delete form.dataset.rgSubmitting;
                });
                document.querySelectorAll('button[data-original-text]').forEach(function (button) {
                    button.disabled = false;
                    button.classList.remove('disabled');
                    if (button.dataset.originalText) button.innerHTML = button.dataset.originalText;
                });
            }, 20000);
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

        // Any btn-success/btn-primary/etc. color is swapped to a neutral
        // btn-secondary for the duration of the "Processing..." state, then
        // restored on reset. A solid green "Processing, please wait..." bar
        // reads as if the action already succeeded before it actually has —
        // neutral gray during the wait avoids that false signal, regardless
        // of which color the button (e.g. "Resolve Incident") normally is.
        const RG_LOADING_COLOR_CLASSES = ['btn-success', 'btn-primary', 'btn-danger', 'btn-warning', 'btn-info', 'btn-dark', 'btn-light'];

        function setButtonLoading(button, message = 'Processing, please wait...') {
            if (!button) return;
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }
            if (!button.dataset.originalColorClass) {
                const colorClass = RG_LOADING_COLOR_CLASSES.find((c) => button.classList.contains(c));
                button.dataset.originalColorClass = colorClass || '';
                if (colorClass) {
                    button.classList.remove(colorClass);
                    button.classList.add('btn-secondary');
                }
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
            if (button.dataset.originalColorClass) {
                button.classList.remove('btn-secondary');
                button.classList.add(button.dataset.originalColorClass);
                delete button.dataset.originalColorClass;
            }
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
 
        function rgFormSubmitter(form, button) {
            if (!button || button.form !== form) return null;
            const type = (button.getAttribute('type') || 'submit').toLowerCase();
            return type === 'submit' ? button : null;
        }

        function rgSubmitWithSubmitter(form, submitter) {
            const restore = [];
            let hidden = null;
            if (submitter) {
                if (submitter.name && !submitter.disabled) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = submitter.name;
                    hidden.value = submitter.value;
                    form.appendChild(hidden);
                }
                [['formaction', 'action'], ['formmethod', 'method'], ['formenctype', 'enctype'], ['formtarget', 'target']].forEach(function (pair) {
                    if (!submitter.hasAttribute(pair[0])) return;
                    restore.push([pair[1], form.getAttribute(pair[1])]);
                    form.setAttribute(pair[1], submitter.getAttribute(pair[0]));
                });
            }
            try {
                HTMLFormElement.prototype.submit.call(form);
            } finally {
                // The submission already captured its data and target, so leave the
                // form as it was in case the page stays (download, new tab).
                if (hidden) hidden.remove();
                restore.forEach(function (pair) {
                    if (pair[1] === null) form.removeAttribute(pair[0]);
                    else form.setAttribute(pair[0], pair[1]);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(function (form) {
                let lastClickedSubmit = null;
                form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(function (btn) {
                    btn.addEventListener('click', function () { lastClickedSubmit = btn; });
                });

                form.addEventListener('submit', function (event) {
                    if (this.classList.contains('no-loading')) return;
                    // A confirm() cancel or another listener already stopped this.
                    if (event.defaultPrevented) return;
                    if (this.dataset.rgSubmitting === '1') {
                        event.preventDefault();
                        return;
                    }

                    // The button that actually submitted this form (e.submitter), so its
                    // name/value and formaction survive the manual submit below.
                    const submitter = rgFormSubmitter(this, event.submitter || lastClickedSubmit);
                    lastClickedSubmit = null;
                    const submitButton = submitter || this.querySelector('button[type="submit"]');
                    const message = submitButton?.dataset.loadingMessage || this.dataset.loadingMessage || 'Processing, please wait...';

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

                    // Chrome cancels a clicked Save when this handler disables
                    // the button or pins the page. Cancel that click, then
                    // post with the form's own submit, which is not tied to
                    // the button. Do this without disabling the button.
                    // form.submit() ignores the clicked button, so carry its name/value
                    // and formaction/formmethod/formenctype/formtarget over first.
                    event.preventDefault();
                    this.dataset.rgSubmitting = '1';
                    rgSubmitWithSubmitter(this, submitter);
                    showLoadingOverlay(message, { lockScroll: false });
                });
            });

            document.querySelectorAll('[data-loading-link]').forEach(function (link) {
                link.addEventListener('click', function () {
                    const href = this.getAttribute('href');
                    if (!href || href.startsWith('#')) return;
                    showLoadingOverlay(this.dataset.loadingMessage || 'Loading, please wait...');
                });
            });

            // Undo any button previously swapped into its "Processing, please
            // wait..." spinner state (see setButtonLoading above). A submit
            // button's innerHTML swap is never re-rendered by the server, so
            // if the browser restores this exact DOM — via bfcache, or via a
            // mobile browser reopening a backgrounded tab without firing our
            // pageshow/reload guard below — the button (e.g. the green
            // "Resolve Incident" button on the resolution-photos form) is
            // left stuck showing the spinner even though nothing is actually
            // submitting. Sweep and reset on every load, not just pageshow.
            function resetAllLoadingButtons() {
                document.querySelectorAll('button[data-original-text]').forEach(resetButtonLoading);
            }

            // Hide overlay as soon as DOM is ready — don't wait for every image/font
            // (window.load), which made icons and page content feel delayed.
            hideLoadingOverlay();
            resetAllLoadingButtons();

            window.addEventListener('pageshow', function () {
                hideLoadingOverlay();
                resetAllLoadingButtons();
            });

            // Defense in depth alongside the 'no-cache' route middleware:
            // if the browser still restores this page from bfcache (back/
            // forward), force a fresh request instead of showing a page
            // whose CSRF token or auth state may no longer match — this is
            // what caused "logout says page expired" / "no permission"
            // after switching accounts in another tab.
            window.addEventListener('pageshow', function (event) {
                if (!event.persisted) return;
                const key = 'raniag-bfcache-reload';
                if (sessionStorage.getItem(key) === location.pathname) {
                    sessionStorage.removeItem(key);
                    hideLoadingOverlay();
                    return;
                }
                sessionStorage.setItem(key, location.pathname);
                window.location.reload();
            });
        });
    </script>
    <x-lightbox />
    <script src="{{ asset('js/help-fab.js') }}?v={{ @filemtime(public_path('js/help-fab.js')) }}"></script>
    <script src="{{ asset('js/evidence-video-player.js') }}?v={{ @filemtime(public_path('js/evidence-video-player.js')) }}"></script>
    @stack('scripts')
</body>
</html>