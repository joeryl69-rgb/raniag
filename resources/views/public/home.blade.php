@extends('layouts.public')

@section('title', 'Home')

@section('content')
<div class="container">
    <section class="rg-portal mb-4">
        <div class="rg-portal-copy">
            <p class="rg-desk-kicker">{{ config('raniag.organization') }}</p>
            <h1 data-rg-hero-title>{{ config('raniag.name') }}</h1>
            <p class="rg-portal-tag">{{ config('raniag.tagline') }}</p>
            <p class="rg-portal-lead">
                File an incident, keep the tracking number, and read official advisories for {{ config('raniag.address.municipality') }}.
            </p>
            @if($alertNote)
                <p class="rg-portal-note">{{ $alertNote }}</p>
            @endif
            <div class="d-flex flex-wrap gap-2" data-rg-hero-btns>
                <a href="{{ route('public.report.create') }}" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-megaphone me-2"></i>{{ __('Report an Incident') }}
                </a>
                <a href="{{ route('public.track') }}" class="btn btn-outline-primary btn-lg px-4">
                    <i class="bi bi-search me-2"></i>{{ __('Track a Report') }}
                </a>
            </div>
        </div>
        <div class="rg-portal-side">
            <img src="/images/pamplona-landmark.jpg" alt="The I love Pamplona sign in front of the Pamplona Cultural and Sports Center" width="1024" height="640">
            <aside class="rg-hotline-card">
                <h2>Emergency lines</h2>
                @forelse($hotlines as $hotline)
                    <a class="rg-hotline-row" href="{{ $hotline->dialHref() }}">
                        <span>
                            <strong>{{ $hotline->name }}</strong>
                            @if($hotline->detail)<small>{{ $hotline->detail }}</small>@endif
                        </span>
                        <em>{{ $hotline->number }}</em>
                    </a>
                @empty
                    <p class="rg-hotline-empty">Call numbers will show here after {{ config('raniag.organization') }} publishes them.</p>
                @endforelse
                <a class="rg-hotline-map" href="{{ route('public.advisories') }}">All advisories{{ $openCenters ? ' · '.$openCenters.' evacuation center'.($openCenters === 1 ? '' : 's').' open' : '' }}</a>
            </aside>
        </div>
    </section>

    <section class="rg-steps" aria-labelledby="rg-how-title">
        <div class="rg-steps-top">
            <div>
                <p class="rg-desk-kicker">Incident desk</p>
                <h2 id="rg-how-title">{{ __('How a report moves') }}</h2>
                <p>You file it, the desk gives you a number, a responder is assigned, and you watch the status.</p>
            </div>
            <button type="button" class="btn btn-outline-primary" id="jo-start-tour-home">
                <i class="bi bi-compass me-1"></i>Guided tour
            </button>
        </div>
        <ol class="rg-steps-rail">
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
            <a href="{{ route('public.advisories') }}" class="small fw-semibold">All advisories</a>
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
.rg-portal {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(280px, .95fr);
    gap: 18px;
    align-items: stretch;
}
.rg-portal-copy, .rg-steps {
    background: #fff;
    border: 1px solid var(--rg-line);
    border-radius: 16px;
    padding: 24px;
}
.rg-portal-copy h1 { margin: 0; font-size: clamp(2rem, 3vw, 2.6rem); font-weight: 800; letter-spacing: -.03em; }
.rg-portal-tag { margin: 8px 0 0; color: var(--rg-brand); font-weight: 700; }
.rg-portal-lead { margin: 10px 0 14px; color: var(--rg-muted); max-width: 38rem; }
.rg-portal-note { margin: 0 0 14px; padding: 10px 12px; border-radius: 12px; background: #f4f7fb; }
.rg-portal-side { display: flex; flex-direction: column; gap: 12px; }
.rg-portal-side img { width: 100%; height: 210px; object-fit: cover; border-radius: 16px; }
.rg-desk-kicker { margin: 0 0 4px; font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--rg-brand); font-weight: 800; }
.rg-steps { margin: 0 0 1.5rem; }
.rg-steps-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.rg-steps-top h2 { margin: 0; font-size: 1.25rem; font-weight: 800; }
.rg-steps-top p { margin: 4px 0 0; color: var(--rg-muted); }
.rg-steps-rail {
    list-style: none;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin: 16px 0 0;
    padding: 0;
}
.rg-steps-rail li { padding: 0; }
.rg-steps-rail i {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    margin-bottom: 8px;
    border-radius: 10px;
    background: #e7f1ff;
    color: var(--rg-brand);
    font-size: 1rem;
}
.rg-steps-rail strong { display: block; font-size: .92rem; }
.rg-steps-rail span { display: block; margin-top: 4px; color: var(--rg-muted); font-size: .82rem; line-height: 1.4; }
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
@media (max-width: 991.98px) {
    .rg-portal, .rg-steps-rail, .rg-home-lower { grid-template-columns: 1fr; }
    .rg-steps-top { flex-wrap: wrap; }
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
