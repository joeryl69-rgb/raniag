@extends('layouts.public')

@section('title', 'Home')

@section('content')
<div class="container">
    <div class="raniag-hero mb-4">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-7 p-4 p-lg-5">
                <p class="text-uppercase small fw-semibold text-white-50 mb-2" data-rg-hero-eyebrow>{{ config('raniag.organization') }}</p>
                <h1 class="display-5 fw-bold mb-2" data-rg-hero-title>{{ config('raniag.name') }}</h1>
                <p class="rg-tagline mb-3" data-rg-hero-tag>{{ config('raniag.tagline') }}</p>
                <p class="lead mb-4 text-white-50" data-rg-hero-desc>
                    {{ __('Report incidents quickly and track their status securely. Your report helps keep our community safe and responsive.') }}
                </p>
                <div class="d-flex flex-wrap gap-3" data-rg-hero-btns>
                    <a href="{{ route('public.report.create') }}" class="btn btn-light btn-lg px-4">
                        <i class="bi bi-megaphone me-2"></i>{{ __('Report an Incident') }}
                    </a>
                    <a href="{{ route('public.track') }}" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-search me-2"></i>{{ __('Track a Report') }}
                    </a>
                </div>
            </div>
            <div class="col-lg-5 raniag-hero-photo">
                <img src="/images/pamplona-landmark.jpg" alt="The I love Pamplona sign in front of the Pamplona Cultural and Sports Center" width="1024" height="640" fetchpriority="high">
            </div>
        </div>
    </div>

    <section class="rg-how" data-rg-hero-card aria-labelledby="rg-how-title">
        <div class="rg-how-head">
            <img class="jo-mascot rg-how-jo" src="/images/guide/jo-greeting.jpg?v=4" alt="JO" width="140" height="176">
            <p class="rg-how-kicker">JO</p>
            <h2 id="rg-how-title" class="rg-how-title">{{ __('How it works') }}</h2>
            <p class="rg-how-lead">Four steps, from the report you file to the status you can check anytime.</p>
        </div>
        <ol class="rg-how-steps">
            <li>
                <span class="rg-how-num">1</span>
                <h3>File the report</h3>
                <p>Describe what happened. Stay anonymous, or leave a number so a responder can reach you. A GPS photo helps {{ config('raniag.organization') }} find the scene.</p>
            </li>
            <li>
                <span class="rg-how-num">2</span>
                <h3>Keep your number</h3>
                <p>You receive a tracking number the moment the report is in. That number is how you follow it later.</p>
            </li>
            <li>
                <span class="rg-how-num">3</span>
                <h3>{{ config('raniag.organization') }} assigns it</h3>
                <p>Staff review the report and send it to the responder who covers that part of Pamplona.</p>
            </li>
            <li>
                <span class="rg-how-num">4</span>
                <h3>Watch the status</h3>
                <p>The tracking page shows submitted, assigned, in progress, or resolved — without calling the office.</p>
            </li>
        </ol>
        <button type="button" class="btn btn-primary" id="jo-start-tour-home">
            <i class="bi bi-compass me-1"></i>Start guided tour
        </button>
    </section>

    {{-- ===================== UPDATES & ANNOUNCEMENTS ===================== --}}
    <div class="mt-5" id="updates" data-rg-reveal data-rg-stagger>
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <span class="rg-announce-badge"><i class="bi bi-megaphone-fill me-1"></i>UPDATES</span>
                <h2 class="h4 fw-bold mb-0">{{ __('Updates and announcements') }}</h2>
            </div>
        </div>

        <div data-live-refresh data-live-refresh-target="#rg-public-announcements" data-live-refresh-interval="4000">
        <div id="rg-public-announcements">
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
                {{ __('No announcements yet. Check back soon for the latest updates from :org.', ['org' => config('raniag.organization')]) }}
            </div>
        @endif
        </div>
        </div>
    </div>

    {{-- ===================== DOWNLOAD APP ===================== --}}
    <div class="rg-download-cta mt-5 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <img src="/images/icons/raniag-master.svg" alt="RANIAG app icon" width="64" height="64" class="rounded-circle">
            <div>
                <h3 class="h5 fw-bold mb-1">{{ __('Get the RANIAG App') }}</h3>
                <p class="small text-white-50 mb-0">{{ __('Install RANIAG on your phone for faster reporting and offline access — works in portrait or landscape.') }}</p>
            </div>
        </div>
        <button type="button" id="rgInstallBtn" class="btn btn-light px-4">
            <i class="bi bi-download me-2"></i>{{ __('Download App') }}
        </button>
    </div>

    {{-- ===================== FAQ ===================== --}}
    <div class="row justify-content-center mt-5" id="faq">
        <div class="col-lg-9">
            <div class="rg-page-head text-center" data-rg-reveal>
                <span class="rg-eyebrow"><i class="bi bi-question-circle"></i>Good to know</span>
                <h2 class="rg-page-title h4">{{ __('Frequently Asked Questions') }}</h2>
            </div>
            <div class="accordion rg-faq-accordion" id="rgFaqAccordion" data-rg-reveal>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#rgFaq1">
                            Is my report really anonymous if I choose to be?
                        </button>
                    </h3>
                    <div id="rgFaq1" class="accordion-collapse collapse show" data-bs-parent="#rgFaqAccordion">
                        <div class="accordion-body">Yes. Leave the name and contact fields blank and no identifying details are attached to the report — only your tracking number links back to it, and only you hold that number.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rgFaq2">
                            How fast will {{ config('raniag.organization') }} respond?
                        </button>
                    </h3>
                    <div id="rgFaq2" class="accordion-collapse collapse" data-bs-parent="#rgFaqAccordion">
                        <div class="accordion-body">Response time depends on the incident's priority and current caseload, but every report is reviewed and assigned to a responder — you'll see the status change on your tracking page as it moves.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rgFaq3">
                            Do I need to install the app to file a report?
                        </button>
                    </h3>
                    <div id="rgFaq3" class="accordion-collapse collapse" data-bs-parent="#rgFaqAccordion">
                        <div class="accordion-body">No — the website works on any browser. Installing the app just makes reporting faster and lets you keep using it with limited functionality when you're offline.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rgFaq4">
                            What happens if my location is outside Pamplona?
                        </button>
                    </h3>
                    <div id="rgFaq4" class="accordion-collapse collapse" data-bs-parent="#rgFaqAccordion">
                        <div class="accordion-body">Your report is flagged as outside MDRRMO Pamplona's area of responsibility and referred onward — it won't just sit unprocessed. Your tracking page will show this status clearly.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rgFaq5">
                            Where can I see community-wide incident trends?
                        </button>
                    </h3>
                    <div id="rgFaq5" class="accordion-collapse collapse" data-bs-parent="#rgFaqAccordion">
                        <div class="accordion-body">The <a href="{{ route('public.dashboard') }}">Community Dashboard</a> shows aggregated, anonymized counts by month, barangay, and incident type — never individual reports or reporter details.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== NEED HELP → SUPPORT CENTER ===================== --}}
    <div class="row justify-content-center mt-5" id="help">
        <div class="col-lg-8">
            <div class="rg-support-card text-center p-4 p-lg-5">
                <div class="text-primary fs-2 mb-2"><i class="bi bi-headset"></i></div>
                <h2 class="h5 fw-bold mb-1">{{ __('Need Help or Have a Concern?') }}</h2>
                <p class="text-muted small mb-3">Encountered an issue, have a suggestion, or want to raise a concern about RANIAG or {{ config('raniag.organization') }}'s response? Our Support Center goes directly to our team.</p>
                <a href="{{ route('public.support') }}" class="rg-support-submit d-inline-flex align-items-center" style="text-decoration:none;">
                    <i class="bi bi-send-fill me-2"></i>{{ __('Go to Support Center') }}
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
