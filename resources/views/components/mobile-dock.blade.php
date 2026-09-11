{{-- Floating bottom navigation dock, mobile only (hidden at lg and up).
     Gives quick access to the most-used destinations without opening the
     full off-canvas sidebar first. "More" opens the existing sidebar. --}}
<nav class="mobile-dock d-lg-none" aria-label="Quick navigation">
    <a href="{{ route('dashboard') }}" class="mobile-dock-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i>
        <span>Home</span>
    </a>

    @if (auth()->user()->isAdministrator())
        <a href="{{ route('admin.incidents.index') }}" class="mobile-dock-link {{ request()->routeIs('admin.incidents.*') ? 'active' : '' }}">
            <i class="bi bi-exclamation-circle"></i>
            <span>Incidents</span>
        </a>
        <a href="{{ route('admin.agencies.index') }}" class="mobile-dock-link {{ request()->routeIs('admin.agencies.*', 'admin.personnel.*', 'admin.personnel_roles.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i>
            <span>Agencies</span>
        </a>
    @elseif (auth()->user()->isAgency() || auth()->user()->isPersonnel())
        <a href="{{ auth()->user()->isPersonnel() ? route('personnel.incidents.index') : route('agency.incidents.index') }}"
           class="mobile-dock-link {{ request()->routeIs('agency.incidents.*', 'personnel.incidents.*') ? 'active' : '' }}">
            <i class="bi bi-card-checklist"></i>
            <span>Dispatches</span>
        </a>
        <a href="{{ route('agency.support.create') }}" class="mobile-dock-link {{ request()->routeIs('agency.support.*') ? 'active' : '' }}">
            <i class="bi bi-headset"></i>
            <span>Support</span>
        </a>
    @endif

    <a href="{{ route('notifications.index') }}" class="mobile-dock-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
        <i class="bi bi-bell"></i>
        <span>Alerts</span>
    </a>

    <button type="button" class="mobile-dock-link mobile-dock-more" onclick="toggleSidebar(true)">
        <i class="bi bi-grid-3x3-gap"></i>
        <span>More</span>
    </button>
</nav>

@once
@push('styles')
<style>
    .mobile-dock {
        position: fixed;
        left: 50%;
        transform: translateX(-50%);
        bottom: max(0.9rem, env(safe-area-inset-bottom, 0px));
        z-index: 1035;
        display: flex;
        align-items: stretch;
        gap: 0.22rem;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 999px;
        padding: 0.38rem 0.42rem;
        box-shadow: 0 0.9rem 1.8rem rgba(15, 23, 42, 0.18);
        max-width: calc(100vw - 1.2rem);
        overflow-x: auto;
    }

    .mobile-dock-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.18rem;
        color: #49576b;
        text-decoration: none;
        background: transparent;
        border: none;
        padding: 0.5rem 0.8rem;
        border-radius: 999px;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        white-space: nowrap;
        min-width: 4rem;
        transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
    }

    .mobile-dock-link i {
        font-size: 1.2rem;
        line-height: 1;
    }

    .mobile-dock-link.active,
    .mobile-dock-link:hover {
        color: #fff;
        background: linear-gradient(135deg, var(--raniag-primary), var(--raniag-accent));
        box-shadow: 0 0.45rem 0.9rem rgba(11, 94, 215, 0.20);
        transform: translateY(-1px);
    }

    .mobile-dock-more {
        border: 1px solid rgba(15, 23, 42, 0.05);
    }

    body.has-mobile-dock #page-content-wrapper .container-fluid {
        padding-bottom: calc(5.4rem + env(safe-area-inset-bottom, 0px));
    }

    @media (min-width: 992px) {
        .mobile-dock { display: none !important; }
    }
</style>
@endpush
@endonce
