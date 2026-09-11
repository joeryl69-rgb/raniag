{{-- Facebook-style account menu: avatar trigger in the top navbar, beside the
     notification bell. Replaces the old sidebar "profile widget" block —
     profile/account actions now live up here instead of at the bottom of
     the sidebar. --}}
<div class="dropdown" id="profile-menu-wrapper">
    <button class="btn btn-light rounded-circle d-flex align-items-center justify-content-center profile-menu-btn p-0"
            type="button" id="profileMenuToggle" data-bs-toggle="dropdown" aria-expanded="false"
            data-bs-display="static" data-bs-offset="0,4"
            aria-label="Account menu">
        @if (auth()->user()->avatar_url)
            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-100 h-100 rounded-circle" style="object-fit:cover;">
        @else
            <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center w-100 h-100 fw-bold small">{{ auth()->user()->initials }}</span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 profile-dropdown" aria-labelledby="profileMenuToggle">
        <div class="px-3 py-3 border-bottom">
            <div class="d-flex align-items-center gap-2 profile-dropdown-card p-2 rounded">
                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden fw-bold" style="width:2.75rem;height:2.75rem;">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-100 h-100" style="object-fit:cover;">
                    @else
                        {{ auth()->user()->initials }}
                    @endif
                </span>
                <div class="text-truncate">
                    <div class="fw-bold text-truncate" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                    @if (auth()->user()->isAdministrator())
                        <span class="badge bg-danger fs-8 fw-normal">Administrator</span>
                    @elseif (auth()->user()->isPersonnel())
                        <span class="badge fs-8 fw-normal" style="background-color: var(--raniag-accent); color: #fff;">Personnel</span>
                    @else
                        <span class="badge fs-8 fw-normal" style="background-color: var(--raniag-accent); color: #fff;">{{ auth()->user()->agency->code ?? 'Agency' }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="py-2">
            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('profile.edit') }}">
                <span class="profile-item-icon"><i class="bi bi-person-circle"></i></span>
                <span>My Profile</span>
            </a>

            @if (auth()->user()->isAgency() || auth()->user()->isPersonnel())
                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('agency.support.create') }}">
                    <span class="profile-item-icon"><i class="bi bi-headset"></i></span>
                    <span>Support Center</span>
                </a>
            @endif
        </div>

        <div class="border-top py-2">
            <form method="POST" action="{{ route('logout') }}" id="profile-menu-logout-form">
                @csrf
                <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="#"
                   onclick="event.preventDefault(); if(this.dataset.submitted) return; this.dataset.submitted='1'; document.getElementById('profile-menu-logout-form').submit();">
                    <span class="profile-item-icon"><i class="bi bi-box-arrow-right"></i></span>
                    <span>Log Out</span>
                </a>
            </form>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .profile-menu-btn {
        width: 2.8rem;
        height: 2.8rem;
        overflow: hidden;
        border: 2px solid rgba(255,255,255,0.9);
        box-shadow: 0 0.3rem 0.8rem rgba(15, 23, 42, 0.12);
        background: linear-gradient(135deg, var(--raniag-surface), rgba(255,255,255,0.72));
    }

    .profile-menu-btn img,
    .profile-menu-btn span {
        width: 100%;
        height: 100%;
        display: block;
    }

    .profile-dropdown {
        width: 300px;
        max-width: min(92vw, 300px);
        z-index: 1055;
    }

    .profile-dropdown-card {
        background-color: var(--raniag-surface);
    }

    .profile-item-icon {
        width: 1.5rem;
        display: inline-flex;
        justify-content: center;
        color: var(--raniag-primary);
    }
</style>
@endpush
@endonce
