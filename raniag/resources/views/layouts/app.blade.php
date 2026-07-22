<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Staff Portal') — {{ config('raniag.organization', 'LGU Pamplona') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/public.css') }}" rel="stylesheet">
    @stack('styles')
    
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f1f5f9;
            overflow-x: hidden;
        }
        
        #wrapper {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        #sidebar-wrapper {
            width: 260px;
            background-color: #0f172a;
            color: #cbd5e1;
            flex-shrink: 0;
            transition: all 0.25s ease;
            display: flex;
            flex-column: column;
            flex-direction: column;
            border-right: 1px solid #1e293b;
        }

        #sidebar-wrapper .sidebar-brand {
            padding: 1.5rem 1.25rem;
            background-color: #020617;
            border-bottom: 1px solid #1e293b;
        }

        #sidebar-wrapper .sidebar-profile {
            padding: 1.25rem;
            background-color: #1e293b;
            border-bottom: 1px solid #0f172a;
        }

        #sidebar-wrapper .nav-link {
            color: #94a3b8;
            padding: 0.8rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 3px solid transparent;
            transition: all 0.15s ease;
        }

        #sidebar-wrapper .nav-link:hover {
            color: #fff;
            background-color: #1e293b;
        }

        #sidebar-wrapper .nav-link.active {
            color: #fff;
            background-color: #1e293b;
            border-left-color: #3b82f6;
            font-weight: 600;
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
 
        #global-loading-overlay .spinner-border {
            width: 3rem;
            height: 3rem;
        }
 
        #global-loading-overlay .loading-text {
            margin-top: 1rem;
            color: #f8fafc;
            font-weight: 600;
        }
 
        .leaflet-control-attribution {
            display: none !important;
        }

        .navbar-top {
            background-color: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
            position: relative;
        }

        /* Sidebar base styles (non-fixed by default for mobile) */
        #sidebar-wrapper {
            width: 260px;
            background-color: #0f172a;
            color: #cbd5e1;
            flex-shrink: 0;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #1e293b;
        }

        /* Make sidebar fixed on desktop only to preserve mobile responsiveness */
        @media (min-width: 992px) {
            #sidebar-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
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
                height: 100vh;
                z-index: 1040;
            }
            #wrapper.toggled #sidebar-wrapper {
                margin-left: 0;
            }
            #sidebar-overlay {
                display: none;
                position: fixed;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0,0,0,0.4);
                z-index: 1030;
                top: 0;
                left: 0;
            }
            #wrapper.toggled #sidebar-overlay {
                display: block;
            }
        }
        
        .fs-8 {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    @php
        // Fetch new/assigned reports badge count dynamically in the layout
        if (auth()->user()->isAdministrator()) {
            $badgeCount = \App\Models\Incident::where('status', \App\Enums\IncidentStatus::Submitted)->count();
        } elseif (auth()->user()->isPersonnel()) {
            $badgeCount = \App\Models\Incident::whereHas('assignments', function ($q) {
                    $q->where('assigned_to', auth()->id())
                        ->where('is_active', true);
                })
                ->where('status', \App\Enums\IncidentStatus::Assigned)
                ->count();
        } else {
            $badgeCount = \App\Models\Incident::where('agency_id', auth()->user()->agency_id)
                ->where('status', \App\Enums\IncidentStatus::Assigned)
                ->count();
        }

        $documentRequestAlertCount = 0;
        if (auth()->user()->agency_id) {
            // Count unread document-request notifications visible to this agency user:
            // their own personal rows, or agency-broadcast rows that are NOT the admin-only one.
            $documentRequestAlertCount = \App\Models\SystemNotification::query()
                ->whereNull('read_at')
                ->where('type', 'document_request')
                ->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->orWhere(function ($q2) {
                            $q2->whereNull('user_id')
                                ->where('data->agency_id', auth()->user()->agency_id)
                                ->where(function ($q3) {
                                    $q3->whereNull('data->audience')
                                        ->orWhere('data->audience', '!=', 'admin');
                                });
                        });
                })
                ->count();
        }

        $notificationCount = \App\Models\SystemNotification::query()
            ->when(auth()->user()->isAdministrator(), function ($query) {
                $query->where(function ($q) {
                    $q->where('user_id', auth()->id())->orWhereNull('user_id');
                });
            })
            ->when(auth()->user()->isPersonnel(), function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->when(! auth()->user()->isAdministrator() && ! auth()->user()->isPersonnel(), function ($query) {
                $query->where(function ($q) {
                    $q->where('user_id', auth()->id())
                        ->orWhere(function ($q2) {
                            $q2->whereNull('user_id')
                               ->where('data->agency_id', auth()->user()->agency_id)
                               ->where(function ($q3) {
                                   $q3->whereNull('data->audience')
                                       ->orWhere('data->audience', '!=', 'admin');
                               });
                        });
                });
            })
            ->whereNull('read_at')
            ->count();

        // Admin document request badge: unread document_request notifications global (user_id null) or targeted
        $adminDocumentRequestAlertCount = 0;
        if (auth()->user()->isAdministrator()) {
            $adminDocumentRequestAlertCount = \App\Models\SystemNotification::query()
                ->whereNull('read_at')
                ->where('type', 'document_request')
                ->where(function ($q) {
                    $q->where('user_id', auth()->id())->orWhereNull('user_id');
                })
                ->selectRaw('COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(`data`, "$.document_request_id"))) as count')
                ->value('count') ?? 0;
        }
    @endphp

    <div id="wrapper">
        <!-- Overlay for mobile toggle -->
        <div id="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-brand">
                <a class="text-white text-decoration-none fw-bold d-flex align-items-center gap-2 fs-5" href="{{ route('dashboard') }}">
                    <span class="bg-primary text-white d-inline-flex align-items-center justify-content-center rounded" style="width: 2rem; height: 2rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </span>
                    <span>RANIAG</span>
                </a>
            </div>

            <!-- Profile Widget -->
            <div class="sidebar-profile d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem;">
                    <i class="bi bi-person-fill fs-4"></i>
                </div>
                <div class="text-truncate">
                    <div class="fw-bold text-white small text-truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                    @if (auth()->user()->isAdministrator())
                        <span class="badge bg-danger fs-8 fw-normal">Administrator</span>
                    @elseif (auth()->user()->isPersonnel())
                        <span class="badge bg-info text-dark fs-8 fw-normal">Personnel</span>
                    @else
                        <span class="badge bg-info text-dark fs-8 fw-normal">{{ auth()->user()->agency->code ?? 'Agency' }}</span>
                    @endif
                </div>
            </div>

            <!-- Nav List -->
            <div class="flex-grow-1 py-3 overflow-y-auto">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                            <i class="bi bi-bell"></i><span>Notifications</span>
                            @if($notificationCount > 0)
                                <span class="badge bg-danger rounded-pill fs-8">{{ $notificationCount }}</span>
                            @endif
                        </a>
                    </li>
 
                    @if(auth()->user()->isAdministrator())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.incidents.*') ? 'active' : '' }}" href="{{ route('admin.incidents.index') }}">
                                <i class="bi bi-exclamation-circle"></i>
                                <span class="flex-grow-1">Incidents</span>
                                @if($badgeCount > 0)
                                    <span class="badge bg-danger rounded-pill fs-8" id="sidebar-badge-count">{{ $badgeCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.agencies.*') ? 'active' : '' }}" href="{{ route('admin.agencies.index') }}">
                                <i class="bi bi-building"></i><span>Agencies</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.sms-logs') ? 'active' : '' }}" href="{{ route('admin.sms-logs') }}">
                                <i class="bi bi-chat-left-text"></i><span>SMS Alerts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                                <i class="bi bi-file-earmark-text"></i><span>Make Report</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.document_requests.*') ? 'active' : '' }}" href="{{ route('admin.document_requests.index') }}">
                                <i class="bi bi-file-earmark-pdf"></i><span>Document Requests</span>
                                @if(isset($adminDocumentRequestAlertCount) && $adminDocumentRequestAlertCount > 0)
                                    <span class="badge bg-danger rounded-pill fs-8">{{ $adminDocumentRequestAlertCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}" href="{{ route('admin.audit-logs') }}">
                                <i class="bi bi-shield-shaded"></i><span>Audit Trails</span>
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('agency.incidents.*') || request()->routeIs('personnel.incidents.*') ? 'active' : '' }}" href="{{ auth()->user()->isPersonnel() ? route('personnel.incidents.index') : route('agency.incidents.index') }}">
                                <i class="bi card-checklist"></i>
                                <span class="flex-grow-1">Dispatches</span>
                                @if($badgeCount > 0)
                                    <span class="badge bg-danger rounded-pill fs-8" id="sidebar-badge-count">{{ $badgeCount }}</span>
                                @endif
                            </a>
                        </li>
                        @if(auth()->user()->agency_id)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('agency.document_requests.*') || request()->routeIs('agency.document_requests.index') ? 'active' : '' }}" href="{{ route('agency.document_requests.index') }}">
                                    <i class="bi bi-file-earmark-pdf"></i><span>Document Requests</span>
                                    @if($documentRequestAlertCount > 0)
                                        <span class="badge bg-danger rounded-pill fs-8">{{ $documentRequestAlertCount }}</span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    @endif

                    <li class="nav-item border-top border-secondary my-2 pt-2">
                        <a class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                            <i class="bi bi-gear"></i><span>My Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
                            @csrf
                            <a class="nav-link text-danger" href="#" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
                                <i class="bi bi-box-arrow-right"></i><span>Log Out</span>
                            </a>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Content Area -->
        <div id="page-content-wrapper">
            <!-- Top bar -->
            <nav class="navbar navbar-top d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-dark d-lg-none" type="button" onclick="toggleSidebar()">
                        <i class="bi bi-list"></i>
                    </button>
                    @if (isset($header))
                        <h4 class="fw-bold mb-0 text-dark fs-5">{{ $header }}</h4>
                    @endif
                </div>

                <div class="text-muted small d-none d-sm-block">
                    <i class="bi bi-calendar3 me-1"></i>{{ date('l, M d, Y') }}
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
            <div class="spinner-border text-white" role="status" aria-hidden="true"></div>
            <div class="loading-text">Processing, please wait...</div>
        </div>
    </div>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('wrapper').classList.toggle('toggled');
        }
 
        function showLoadingOverlay(message = 'Processing, please wait...') {
            const overlay = document.getElementById('global-loading-overlay');
            if (!overlay) return;
            const label = overlay.querySelector('.loading-text');
            if (label) {
                label.textContent = message;
            }
            overlay.classList.remove('d-none');
        }
 
        function hideLoadingOverlay() {
            const overlay = document.getElementById('global-loading-overlay');
            if (overlay) {
                overlay.classList.add('d-none');
            }
        }
 
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function () {
                    if (!this.classList.contains('no-loading')) {
                        showLoadingOverlay();
                    }
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>