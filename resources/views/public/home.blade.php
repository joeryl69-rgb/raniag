@extends('layouts.public')

@section('title', 'Home')

@section('content')
<div class="container">
    <div class="raniag-hero p-4 p-lg-5 mb-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <p class="text-uppercase small fw-semibold text-white-50 mb-2">{{ config('raniag.organization') }}</p>
                <h1 class="display-5 fw-bold mb-2">{{ config('raniag.name') }}</h1>
                <p class="rg-tagline mb-3">{{ config('raniag.tagline') }}</p>
                <p class="lead mb-4 text-white-50">
                    Report incidents quickly and track their status securely. Your report helps keep our community safe and responsive.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('public.report.create') }}" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-megaphone me-2"></i>Report an Incident
                    </a>
                    <a href="{{ route('public.track') }}" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-search me-2"></i>Track a Report
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card raniag-card border-0">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3">How it works</h2>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Submit your incident report (anonymous or with contact details).</li>
                            <li class="mb-2">Receive a unique tracking number instantly.</li>
                            <li class="mb-2">{{ config('raniag.organization') }} staff review and assign your report to the proper responder.</li>
                            <li>Track status updates anytime using your tracking number.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-2">
        <div class="col-md-4">
            <div class="card raniag-card h-100 text-center p-4">
                <div class="text-primary fs-2 mb-3"><i class="bi bi-eye-slash"></i></div>
                <h3 class="h6 fw-bold">Report Anonymously, or Not</h3>
                <p class="text-muted small mb-0">Skip your name and contact details entirely, or leave them so a responder can reach you directly — your choice.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card raniag-card h-100 text-center p-4">
                <div class="text-primary fs-2 mb-3"><i class="bi bi-geo-alt"></i></div>
                <h3 class="h6 fw-bold">GPS-Tagged Evidence</h3>
                <p class="text-muted small mb-0">Photos from the built-in camera carry your exact coordinates and barangay, so {{ config('raniag.organization') }} finds the scene fast.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card raniag-card h-100 text-center p-4">
                <div class="text-primary fs-2 mb-3"><i class="bi bi-bell"></i></div>
                <h3 class="h6 fw-bold">Real-Time Status Tracking</h3>
                <p class="text-muted small mb-0">Your tracking number shows exactly where your report stands — submitted, assigned, in progress, or resolved.</p>
            </div>
        </div>
    </div>

    {{-- ===================== UPDATES & ANNOUNCEMENTS ===================== --}}
    <div class="mt-5" id="updates">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <span class="rg-announce-badge"><i class="bi bi-megaphone-fill me-1"></i>UPDATES</span>
                <h2 class="h4 fw-bold mb-0">Updates &amp; Announcements</h2>
            </div>
        </div>

        @if($announcements->isNotEmpty())
            <div class="row g-3">
                @foreach($announcements as $item)
                    <div class="col-md-4">
                        <div class="rg-announce-card">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:38px;height:38px;">
                                    <i class="bi {{ $item->icon ?? 'bi-megaphone-fill' }}"></i>
                                </span>
                                @if($item->badge)<span class="rg-announce-badge mb-0">{{ $item->badge }}</span>@endif
                            </div>
                            <h3 class="h6 fw-bold mb-1">{{ $item->title }}</h3>
                            <p class="small text-muted mb-2">{{ Str::limit($item->body, 140) }}</p>
                            <div class="rg-announce-date">{{ optional($item->published_at)->format('M d, Y') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rg-support-card text-center text-muted py-4">
                <i class="bi bi-megaphone fs-2 d-block mb-2"></i>
                No announcements yet. Check back soon for the latest updates from {{ config('raniag.organization') }}.
            </div>
        @endif
    </div>

    {{-- ===================== DOWNLOAD APP ===================== --}}
    <div class="rg-download-cta mt-5 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <img src="/images/icons/icon-96x96.png" alt="RANIAG app icon" width="64" height="64" class="rounded-4">
            <div>
                <h3 class="h5 fw-bold mb-1">Get the RANIAG App</h3>
                <p class="small text-white-50 mb-0">Install RANIAG on your phone for faster reporting and offline access — works in portrait or landscape.</p>
            </div>
        </div>
        <button type="button" id="rgInstallBtn" class="btn btn-light px-4">
            <i class="bi bi-download me-2"></i>Download App
        </button>
    </div>

    {{-- ===================== NEED HELP → SUPPORT CENTER ===================== --}}
    <div class="row justify-content-center mt-5" id="help">
        <div class="col-lg-8">
            <div class="rg-support-card text-center p-4 p-lg-5">
                <div class="text-primary fs-2 mb-2"><i class="bi bi-headset"></i></div>
                <h2 class="h5 fw-bold mb-1">Need Help or Have a Concern?</h2>
                <p class="text-muted small mb-3">Encountered an issue, have a suggestion, or want to raise a concern about RANIAG or {{ config('raniag.organization') }}'s response? Our Support Center goes directly to our team.</p>
                <a href="{{ route('public.support') }}" class="rg-support-submit d-inline-flex align-items-center" style="text-decoration:none;">
                    <i class="bi bi-send-fill me-2"></i>Go to Support Center
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // PWA install prompt for the "Download App" button.
    let rgDeferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        rgDeferredPrompt = e;
    });
    document.getElementById('rgInstallBtn')?.addEventListener('click', async () => {
        if (rgDeferredPrompt) {
            rgDeferredPrompt.prompt();
            await rgDeferredPrompt.userChoice;
            rgDeferredPrompt = null;
        } else {
            alert('To install RANIAG: open your browser menu and choose "Add to Home Screen" or "Install App".');
        }
    });
</script>
@endpush
@endsection
