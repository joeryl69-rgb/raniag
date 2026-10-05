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

    <section class="rg-desk" data-rg-hero-card aria-labelledby="rg-how-title">
        <div class="rg-desk-top">
            <img class="jo-mascot rg-desk-jo" src="/images/guide/jo-greeting.jpg?v=4" alt="JO" width="88" height="110">
            <div>
                <p class="rg-desk-kicker">Incident desk</p>
                <h2 id="rg-how-title">{{ __('How a report moves') }}</h2>
                <p>One path. You file it, the desk gives you a number, a responder is assigned, and you watch the status.</p>
            </div>
            <button type="button" class="btn btn-primary" id="jo-start-tour-home">
                <i class="bi bi-compass me-1"></i>Start guided tour
            </button>
        </div>
        <ol class="rg-desk-rail">
            <li>
                <i class="bi bi-megaphone"></i>
                <strong>1 · File</strong>
                <span>Type plus a GPS photo. Details are optional. No photo means leave a number.</span>
            </li>
            <li>
                <i class="bi bi-ticket-perforated"></i>
                <strong>2 · Number</strong>
                <span>The tracking number is issued the moment the report is in.</span>
            </li>
            <li>
                <i class="bi bi-signpost-split"></i>
                <strong>3 · Assign</strong>
                <span>{{ config('raniag.organization') }} sends it to the responder for that part of Pamplona.</span>
            </li>
            <li>
                <i class="bi bi-activity"></i>
                <strong>4 · Watch</strong>
                <span>Submitted, assigned, in progress, or resolved — on this site, not by phone.</span>
            </li>
        </ol>
    </section>

    {{-- ===================== UPDATES & ANNOUNCEMENTS ===================== --}}
    <div class="mt-4" id="updates" data-rg-reveal data-rg-stagger>
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <span class="rg-announce-badge"><i class="bi bi-megaphone-fill me-1"></i>UPDATES</span>
                <h2 class="h4 fw-bold mb-0">{{ __('Updates and announcements') }}</h2>
            </div>
        </div>

        <div data-live-refresh data-live-refresh-target="#rg-public-announcements" data-live-refresh-interval="4000">
        <div id="rg-public-announcements">
        @if($announcements->isNotEmpty())
            <div class="rg-log">
                @foreach($announcements as $item)
                    <article class="rg-log-item rg-announce-card">
                        <time datetime="{{ optional($item->published_at)->toDateString() }}">{{ optional($item->published_at)->format('M d') }}</time>
                        <div>
                            <div class="rg-log-meta">
                                <i class="bi {{ $item->icon ?? 'bi-megaphone-fill' }}"></i>
                                @if($item->badge)<span>{{ $item->badge }}</span>@endif
                            </div>
                            <h3>{{ $item->title }}</h3>
                            <p>{{ Str::limit($item->body, 160) }}</p>
                        </div>
                    </article>
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

    <div class="rg-home-lower" id="faq">
    {{-- ===================== FAQ ===================== --}}
    <div>
            <div class="rg-page-head" data-rg-reveal>
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
                        <div class="accordion-body">Yes, when you attach a GPS photo, video, or file. With that evidence you can turn on Report anonymously and leave your name blank. Without a photo, a phone number or email is required so the team can verify the report. Only you hold the tracking number.</div>
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

    {{-- App install and the support desk sit beside the questions. --}}
    <aside class="rg-home-aside" id="help">
        <div class="rg-download-cta">
            <img src="/images/icons/raniag-master.svg" alt="RANIAG app icon" width="52" height="52" class="rounded-circle">
            <div>
                <h3 class="h6 fw-bold mb-1">{{ __('Get the RANIAG App') }}</h3>
                <p class="small text-white-50 mb-2">{{ __('Install it for faster reporting and offline access.') }}</p>
                <button type="button" id="rgInstallBtn" class="btn btn-light btn-sm px-3">
                    <i class="bi bi-download me-1"></i>{{ __('Download App') }}
                </button>
            </div>
        </div>
        <div class="rg-support-card p-4">
            <div class="text-primary fs-3 mb-2"><i class="bi bi-headset"></i></div>
            <h2 class="h5 fw-bold mb-1">{{ __('Need Help or Have a Concern?') }}</h2>
            <p class="text-muted small mb-3">A problem with the site, a suggestion, or a concern about a response goes straight to the team.</p>
            <a href="{{ route('public.support') }}" data-rg-tour="support" class="rg-support-submit d-inline-flex align-items-center" style="text-decoration:none;">
                <i class="bi bi-send-fill me-2"></i>{{ __('Go to Support Center') }}
            </a>
        </div>
    </aside>
    </div>
</div>

@push('styles')
<style>
.rg-desk {
    margin: .25rem 0 1.75rem;
    padding: 22px 22px 8px;
    border-radius: 22px;
    background:
        linear-gradient(180deg, rgba(11, 94, 215, .08), transparent 90px),
        #0f1c33;
    color: #e8eef8;
    box-shadow: 0 18px 40px -28px rgba(8, 15, 28, .8);
}
.rg-desk-top { display: flex; align-items: center; gap: 16px; }
.rg-desk-jo {
    width: 72px;
    height: 72px;
    object-fit: cover;
    border-radius: 18px;
    background: #fff;
    flex-shrink: 0;
}
.rg-desk-kicker {
    margin: 0 0 2px;
    font-size: .72rem;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: #93c5fd;
    font-weight: 700;
}
.rg-desk-top h2 { margin: 0 0 .2rem; font-size: clamp(1.35rem, 2vw, 1.75rem); font-weight: 800; color: #fff; }
.rg-desk-top p { margin: 0; color: #b7c3d6; max-width: 40rem; }
.rg-desk-top .btn { margin-left: auto; flex-shrink: 0; }
.rg-desk-rail {
    list-style: none;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0;
    margin: 18px 0 8px;
    padding: 18px 0 10px;
    position: relative;
}
.rg-desk-rail::before {
    content: "";
    position: absolute;
    left: 8%;
    right: 8%;
    top: 40px;
    height: 2px;
    background: rgba(147, 197, 253, .25);
}
.rg-desk-rail::after {
    content: "";
    position: absolute;
    left: 8%;
    top: 40px;
    height: 2px;
    width: 18%;
    background: #60a5fa;
    box-shadow: 0 0 12px #60a5fa;
    animation: rg-desk-run 4.8s ease-in-out infinite;
}
.rg-desk-rail li { position: relative; padding: 0 12px; text-align: center; }
.rg-desk-rail i {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    margin: 0 auto 10px;
    border-radius: 14px;
    background: #173055;
    border: 1px solid rgba(147, 197, 253, .35);
    color: #bfdbfe;
    font-size: 1.15rem;
    position: relative;
    z-index: 1;
}
.rg-desk-rail strong { display: block; color: #fff; font-size: .92rem; }
.rg-desk-rail span { display: block; margin-top: 4px; color: #9aabc2; font-size: .8rem; line-height: 1.4; }
.rg-log { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; }
.rg-log-item.rg-announce-card {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    height: auto;
    border-left: 3px solid var(--raniag-primary, #0b5ed7);
}
.rg-log-item time {
    font-weight: 800;
    font-size: .78rem;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--raniag-primary, #0b5ed7);
}
.rg-log-meta { display: flex; align-items: center; gap: 8px; color: #5b6780; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.rg-log-item h3 { margin: 2px 0; font-size: 1rem; font-weight: 800; }
.rg-log-item p { margin: 0; color: #5b6780; font-size: .86rem; }
.rg-home-lower {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(280px, .75fr);
    gap: 18px;
    align-items: stretch;
    margin-top: 1.5rem;
}
.rg-home-aside { display: flex; flex-direction: column; gap: 12px; }
.rg-home-aside .rg-download-cta,
.rg-home-aside .rg-support-card { margin: 0; flex: 1; }
.rg-home-aside .rg-download-cta { display: flex; align-items: center; gap: 14px; padding: 20px; }
@keyframes rg-desk-run {
    0% { transform: translateX(0); }
    100% { transform: translateX(420%); }
}
@media (max-width: 991.98px) {
    .rg-desk-top { flex-wrap: wrap; }
    .rg-desk-top .btn { margin-left: 0; }
    .rg-desk-rail { grid-template-columns: 1fr 1fr; gap: 16px; }
    .rg-desk-rail::before, .rg-desk-rail::after { display: none; }
    .rg-desk-rail li { text-align: left; display: grid; grid-template-columns: 44px minmax(0, 1fr); column-gap: 10px; }
    .rg-desk-rail i { margin: 0; }
    .rg-desk-rail strong, .rg-desk-rail span { grid-column: 2; }
    .rg-home-lower { grid-template-columns: 1fr; }
}
@media (max-width: 575.98px) {
    .rg-desk-rail { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    .rg-desk-rail::after { animation: none; width: 84%; }
}
</style>
@endpush

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
