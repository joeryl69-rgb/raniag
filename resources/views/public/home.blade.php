@extends('layouts.public')

@section('title', 'Home')

@section('content')
<div class="container rg-home-container">
    <div class="rg-cursor-field" aria-hidden="true"></div>
    <section class="rg-home-hero" aria-labelledby="home-title">
        <div class="rg-home-hero-photo" aria-hidden="true"></div>
        <div class="rg-home-hero-content">
            <p class="rg-home-kicker" data-rg-hero-eyebrow><span></span>{{ config('raniag.organization') }} <b>/</b> PUBLIC SAFETY DESK</p>
            <h1 id="home-title" data-rg-hero-title>When something happens,<br><em>start here.</em></h1>
            <p class="rg-home-tagline" data-rg-hero-tag>{{ config('raniag.tagline') }}</p>
            <p class="rg-home-lead" data-rg-hero-desc>Send a report to the local response team, follow its progress, and see public safety information for Pamplona.</p>
            <div class="rg-home-actions" data-rg-hero-btns>
                <a href="{{ route('public.report.create') }}" class="rg-home-primary-action">
                    <span class="bi bi-plus-lg" aria-hidden="true"></span> Report an incident
                </a>
                <a href="{{ route('public.track') }}" class="rg-home-secondary-action">
                    Track a report <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
        <div class="rg-home-coordinate" aria-hidden="true">
            <span>17° 27′ N</span><i></i><span>121° 28′ E</span><b>PAMPLONA · CAGAYAN</b>
        </div>
        <div class="rg-home-orbit" aria-hidden="true"><span></span><i></i><b></b></div>
    </section>

    <nav class="rg-home-shortcuts" aria-label="Public information">
        <a href="{{ route('public.hazard.map') }}" data-rg-tour="hazard">
            <span class="rg-shortcut-index">01</span><i class="bi bi-map" aria-hidden="true"></i>
            <span><small>Explore</small><strong>Live map</strong></span><i class="bi bi-arrow-up-right rg-shortcut-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('public.dashboard') }}" data-rg-tour="dashboard">
            <span class="rg-shortcut-index">02</span><i class="bi bi-bar-chart-line" aria-hidden="true"></i>
            <span><small>Understand</small><strong>Community reports</strong></span><i class="bi bi-arrow-up-right rg-shortcut-arrow" aria-hidden="true"></i>
        </a>
        <a href="{{ route('public.support') }}" data-rg-tour="support">
            <span class="rg-shortcut-index">03</span><i class="bi bi-chat-square-text" aria-hidden="true"></i>
            <span><small>Get assistance</small><strong>Support center</strong></span><i class="bi bi-arrow-up-right rg-shortcut-arrow" aria-hidden="true"></i>
        </a>
    </nav>

    <section class="rg-report-journey" id="how-it-works" aria-labelledby="rg-how-title" data-rg-journey>
        <header class="rg-journey-heading">
            <p class="rg-section-index"><span>FIELD GUIDE</span> <i></i> 01 — 04</p>
            <h2 id="rg-how-title">A report becomes<br><em>a response.</em></h2>
            <p>Follow the path from first observation to a clear update from MDRRMO Pamplona.</p>
        </header>
        <div class="rg-journey-layout">
            <div class="rg-journey-visual">
                <div class="rg-journey-visual-top"><span><i></i> REPORT PATH</span><span id="rg-journey-counter">01 / 04</span></div>
                <div class="rg-journey-map" aria-hidden="true">
                    <span class="rg-map-grid"></span><span class="rg-map-contour contour-one"></span><span class="rg-map-contour contour-two"></span>
                    <span class="rg-map-road road-one"></span><span class="rg-map-road road-two"></span><span class="rg-map-route"></span>
                    <span class="rg-map-point point-one"></span><span class="rg-map-point point-two"></span><span class="rg-map-point point-three"></span>
                    <span class="rg-map-axis axis-x">121° 28′ E</span><span class="rg-map-axis axis-y">17° 27′ N</span>
                </div>
                <div class="rg-journey-mascot">
                    <img id="rg-journey-jo" src="/images/guide/jo-greeting.png?v=1" alt="JO, RANIAG's reporting guide" width="190" height="253">
                    <div class="rg-journey-jo-note"><span>JO · YOUR GUIDE</span><strong id="rg-journey-jo-line">Every clear report helps the team know where to begin.</strong></div>
                </div>
                <div class="rg-journey-visual-foot"><span id="rg-journey-status">01 — INTAKE</span><span class="rg-signal-bars"><i></i><i></i><i></i><i></i><i></i></span></div>
            </div>
            <div class="rg-journey-steps">
                <article class="rg-journey-step is-active" data-journey-step="0" data-jo="/images/guide/jo-greeting.png?v=1" data-status="01 — INTAKE" data-line="Every clear report helps the team know where to begin.">
                    <span class="rg-journey-step-no">01</span>
                    <div><p>FIRST, TELL US</p><h3>Send what you know.</h3><span>Choose the incident type and pin the location. A photo or a few details can help responders prepare.</span><a href="{{ route('public.report.create') }}">Start a report <i class="bi bi-arrow-up-right"></i></a></div>
                </article>
                <article class="rg-journey-step" data-journey-step="1" data-jo="/images/guide/jo-camera.png?v=1" data-status="02 — REFERENCE" data-line="Keep your reference number somewhere safe. It opens your case updates.">
                    <span class="rg-journey-step-no">02</span>
                    <div><p>THEN, KEEP YOUR NUMBER</p><h3>Your case gets a reference.</h3><span>After you send the report, save the tracking number. You can use it later without creating an account.</span><a href="{{ route('public.track') }}">Find a report <i class="bi bi-arrow-up-right"></i></a></div>
                </article>
                <article class="rg-journey-step" data-journey-step="2" data-jo="/images/guide/jo-map.png?v=1" data-status="03 — COORDINATION" data-line="The right location helps MDRRMO coordinate a response in the right area.">
                    <span class="rg-journey-step-no">03</span>
                    <div><p>THE TEAM COORDINATES</p><h3>MDRRMO reviews and routes it.</h3><span>The local response team reviews incoming reports and coordinates with responders for the affected area.</span><a href="{{ route('public.hazard.map') }}">Open the live map <i class="bi bi-arrow-up-right"></i></a></div>
                </article>
                <article class="rg-journey-step" data-journey-step="3" data-jo="/images/guide/jo-clipboard.png?v=1" data-status="04 — FOLLOW-THROUGH" data-line="Check your case page for the latest status and messages from the team.">
                    <span class="rg-journey-step-no">04</span>
                    <div><p>FINALLY, FOLLOW THROUGH</p><h3>See what happens next.</h3><span>Open your case page to check its status and read any updates or requests from the response team.</span><a href="{{ route('public.track') }}">Track your report <i class="bi bi-arrow-up-right"></i></a></div>
                </article>
            </div>
        </div>
        <button type="button" class="rg-journey-tour" id="jo-start-tour-home"><i class="bi bi-compass" aria-hidden="true"></i> Take a quick site tour <span>JO can show you around</span></button>
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

    <section class="rg-home-briefing" aria-labelledby="rg-briefing-title">
        <header class="rg-home-briefing-head" data-rg-reveal>
            <p class="rg-section-index"><span>PUBLIC INFORMATION</span><i></i> 02 — 02</p>
            <h2 id="rg-briefing-title">Stay ready between<br><em>reports.</em></h2>
            <p>Local updates, practical answers, and direct help from the Pamplona response desk.</p>
        </header>
        <div class="rg-home-briefing-grid">
            <div class="rg-home-briefing-main">
                <section class="rg-home-updates" id="updates" aria-labelledby="rg-updates-title" data-rg-reveal>
                    <div class="rg-home-module-head">
                        <div>
                            <span class="rg-announce-badge"><i class="bi bi-megaphone-fill me-1"></i>UPDATES</span>
                            <h2 id="rg-updates-title">{{ __('Updates and announcements') }}</h2>
                        </div>
                        <span class="rg-home-live-tag"><i></i> LOCAL DESK</span>
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
                            <div class="rg-home-empty-update">
                                <span class="bi bi-broadcast-pin" aria-hidden="true"></span>
                                <div><strong>No new advisories</strong><p>Check back here for updates from {{ config('raniag.organization') }}.</p></div>
                            </div>
                        @endif
                        </div>
                    </div>
                </section>

                <section class="rg-home-faq" id="faq" aria-labelledby="rg-faq-title" data-rg-reveal>
                    <div class="rg-home-module-head">
                        <div>
                            <span class="rg-announce-badge"><i class="bi bi-question-circle me-1"></i>FIELD NOTES</span>
                            <h2 id="rg-faq-title">{{ __('Frequently Asked Questions') }}</h2>
                        </div>
                        <a href="{{ route('public.support') }}">Ask the support team <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="accordion rg-faq-accordion" id="rgFaqAccordion">
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
                </section>
            </div>

            <aside class="rg-home-aside" id="help" aria-label="Install the app or contact support">
                <article class="rg-download-cta">
                    <div class="rg-app-orbit" aria-hidden="true"><span></span><i></i><b></b></div>
                    <div class="rg-app-label"><i></i> RANIAG FIELD TOOL</div>
                    <img src="/images/guide/jo-phone.png?v=1" alt="JO holding a phone with the location ready" width="180" height="240" loading="lazy">
                    <div class="rg-app-copy">
                        <span class="rg-app-index">APP / 01</span>
                        <h2>{{ __('RANIAG, ready when you are.') }}</h2>
                        <p>{{ __('Install it for faster reporting and offline access.') }}</p>
                        <button type="button" id="rgInstallBtn" class="rg-install-button" aria-describedby="rg-install-feedback">
                            <i class="bi bi-download" aria-hidden="true"></i>{{ __('Download App') }}<span class="bi bi-arrow-up-right" aria-hidden="true"></span>
                        </button>
                        <p class="rg-install-feedback" id="rg-install-feedback" role="status" aria-live="polite"></p>
                    </div>
                </article>
                <article class="rg-home-support-card">
                    <div class="rg-support-orbit" aria-hidden="true"><i class="bi bi-headset"></i></div>
                    <span class="rg-app-index">HUMAN SUPPORT / 02</span>
                    <h2>{{ __('Need help or have a concern?') }}</h2>
                    <p>A problem with the site, a suggestion, or a response concern goes straight to the team.</p>
                    <a href="{{ route('public.support') }}" data-rg-tour="support" class="rg-home-support-button">
                        <i class="bi bi-chat-square-dots" aria-hidden="true"></i>{{ __('Go to Support Center') }}<i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </article>
            </aside>
        </div>
    </section>
</div>

@push('styles')
<style>
.rg-home-hero { min-height: 610px; position: relative; isolation: isolate; display: flex; align-items: end; overflow: hidden; margin: 0 calc((100vw - min(100vw - 2rem, 1320px))/ -2); padding: clamp(26px, 7vw, 88px) max(24px, calc((100vw - min(100vw - 2rem, 1320px))/2)); background: #0b1726; color: #f4f7f6; }
.rg-home-container { position: relative; isolation: isolate; }
.rg-home-container > :not(.rg-cursor-field) { position: relative; z-index: 2; }
.rg-cursor-field { position: fixed; inset: 0; z-index: 1; opacity: 0; pointer-events: none; background: radial-gradient(420px circle at var(--rg-pointer-x, 50vw) var(--rg-pointer-y, 50vh), rgba(13,132,118,.24), transparent 74%), radial-gradient(120px circle at var(--rg-pointer-x, 50vw) var(--rg-pointer-y, 50vh), rgba(72,200,179,.12), transparent 78%); transition: opacity .24s ease; }
.rg-cursor-field.is-active { opacity: 1; }
html[data-public-theme="dark"] .rg-cursor-field { background: radial-gradient(460px circle at var(--rg-pointer-x, 50vw) var(--rg-pointer-y, 50vh), rgba(47,205,173,.3), transparent 74%), radial-gradient(130px circle at var(--rg-pointer-x, 50vw) var(--rg-pointer-y, 50vh), rgba(107,242,211,.16), transparent 78%); }
.rg-home-hero-photo { position: absolute; z-index: -2; inset: 0; background: linear-gradient(90deg, rgba(7,20,31,.96) 0%, rgba(7,20,31,.83) 34%, rgba(7,20,31,.32) 72%, rgba(7,20,31,.12) 100%), linear-gradient(0deg, rgba(7,20,31,.52), transparent 54%), url('/images/pamplona-landmark.jpg') center 54% / cover no-repeat; transform: scale(1.025); }
.rg-home-hero::before { content:""; position:absolute; z-index:-1; inset:0; opacity:.32; background-image:linear-gradient(rgba(173,213,218,.12) 1px,transparent 1px),linear-gradient(90deg,rgba(173,213,218,.12) 1px,transparent 1px); background-size:44px 44px; mask-image:linear-gradient(90deg,#000 0%,transparent 76%); }
.rg-home-hero-content { width: min(760px, 68%); position:relative; z-index:1; padding-bottom:24px; }
.rg-home-kicker,.rg-section-index { display:flex; align-items:center; gap:10px; color:#a9c2c8; font:700 .68rem/1.2 ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.15em; text-transform:uppercase; }
.rg-home-kicker span,.rg-journey-visual-top i { width:8px; height:8px; border-radius:50%; background:#48d5a2; box-shadow:0 0 0 4px rgba(72,213,162,.14); }
.rg-home-kicker b { color:#e9bd5c; font-weight:500; }
.rg-home-hero h1 { max-width:12ch; margin:24px 0 12px; font-size:clamp(3.2rem,8vw,7.1rem); font-weight:750; line-height:.91; letter-spacing:-.075em; color:#f5f6f1; }
.rg-home-hero h1 em,.rg-journey-heading h2 em { color:#54c3ad; font-style:normal; }
.rg-home-tagline { margin:26px 0 8px; color:#e9bd5c; font:700 .78rem/1.5 ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.14em; text-transform:uppercase; }
.rg-home-lead { max-width:49ch; margin:0; color:#d0dce0; font-size:1.05rem; line-height:1.7; }
.rg-home-actions { display:flex; flex-wrap:wrap; align-items:center; gap:20px; margin-top:30px; }
.rg-home-primary-action { min-height:54px; display:inline-flex; align-items:center; gap:10px; padding:0 20px; border-radius:4px; background:#087b78; color:#fff; text-decoration:none; font-weight:750; transition:background .18s ease,transform .18s ease; }
.rg-home-primary-action:hover { transform:translateY(-2px); background:#0a9187; color:#fff; }
.rg-home-primary-action .bi { font-size:1.2rem; }
.rg-home-secondary-action { display:inline-flex; align-items:center; gap:14px; color:#e9eff0; font-size:.9rem; font-weight:700; text-decoration:none; }
.rg-home-secondary-action:hover { color:#67d2be; }
.rg-home-coordinate { position:absolute; right:28px; bottom:24px; display:grid; grid-template-columns:auto auto; align-items:center; gap:6px 12px; padding:12px 15px; border:1px solid rgba(236,246,246,.35); background:rgba(9,26,39,.68); color:#e2edef; font:700 .66rem/1.2 ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.08em; backdrop-filter:blur(8px); }
.rg-home-coordinate i { height:1px; width:23px; background:#e9bd5c; }
.rg-home-coordinate b { grid-column:1/-1; padding-top:7px; border-top:1px solid rgba(255,255,255,.17); color:#8fb2bd; font-size:.58rem; letter-spacing:.16em; }
.rg-home-orbit { position:absolute; right:15%; top:17%; width:clamp(130px,18vw,245px); aspect-ratio:1; border:1px solid rgba(99,209,190,.55); border-radius:50%; opacity:.72; }
.rg-home-orbit::before,.rg-home-orbit::after { content:""; position:absolute; inset:14%; border:1px dashed rgba(199,232,225,.34); border-radius:50%; }
.rg-home-orbit::after { inset:32%; border-style:solid; border-color:rgba(233,189,92,.62); }
.rg-home-orbit span { position:absolute; inset:0; border-radius:50%; background:conic-gradient(from 320deg,transparent 0 72%,rgba(58,216,170,.4) 88%,transparent 100%); animation:rg-orbit 9s linear infinite; }
.rg-home-orbit i,.rg-home-orbit b { position:absolute; width:8px;height:8px;border:2px solid #83ebcc;background:#0b1726;border-radius:50%;top:23%;left:16%;box-shadow:0 0 18px #48d5a2; }
.rg-home-orbit b { top:64%;left:auto;right:23%;border-color:#e9bd5c;box-shadow:0 0 18px #e9bd5c; }
.rg-home-shortcuts { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); margin:22px 0 0; border-top:1px solid #b9c8ca; border-bottom:1px solid #b9c8ca; }
.rg-home-shortcuts>a { display:grid; grid-template-columns:30px 25px minmax(0,1fr) 18px; align-items:center; gap:12px; min-height:104px; padding:14px 18px; color:#203748; text-decoration:none; transition:background .2s ease,color .2s ease; }
.rg-home-shortcuts>a+a { border-left:1px solid #cbd6d7; }
.rg-home-shortcuts>a:hover { background:#deeeeb; color:#075b64; }
.rg-shortcut-index { align-self:start; padding-top:5px; color:#738891; font:700 .65rem/1 ui-monospace,monospace; }
.rg-home-shortcuts>a>.bi { color:#087b78; font-size:1.2rem; }
.rg-home-shortcuts small,.rg-home-shortcuts strong { display:block; }
.rg-home-shortcuts small { margin-bottom:4px; color:#627983; font:700 .61rem/1.1 ui-monospace,monospace; letter-spacing:.12em; text-transform:uppercase; }
.rg-home-shortcuts strong { font-size:.94rem; }
.rg-home-shortcuts .rg-shortcut-arrow { color:#6f858f; font-size:.85rem; }
.rg-report-journey { padding:clamp(64px,9vw,118px) 0 70px; }
.rg-journey-heading { max-width:690px; margin-bottom:40px; }
.rg-section-index { margin:0 0 18px; color:#607682; }
.rg-section-index span { color:#087b78; }
.rg-section-index i { width:42px;height:1px;background:#9db0b4; }
.rg-journey-heading h2 { margin:0; color:#102638; font-size:clamp(2.5rem,6vw,5.4rem); line-height:.98; letter-spacing:-.065em; }
.rg-journey-heading h2 em { color:#087b78; }
.rg-journey-heading>p:last-child { max-width:46ch; margin:18px 0 0; color:#586e79; line-height:1.7; }
.rg-journey-layout { display:grid; grid-template-columns:minmax(300px,.92fr) minmax(380px,1fr); align-items:start; gap:clamp(32px,7vw,110px); }
.rg-journey-visual { position:sticky; top:calc(50vh - 245px); min-height:480px; overflow:hidden; border:1px solid #244453; border-radius:7px; background:#0e2432; color:#e7f0f1; box-shadow:0 22px 52px -34px rgba(10,28,38,.66); }
.rg-journey-visual-top,.rg-journey-visual-foot { position:relative; z-index:3; display:flex; align-items:center; justify-content:space-between; padding:15px 18px; color:#adc4ca; font:700 .62rem/1.2 ui-monospace,monospace; letter-spacing:.12em; }
.rg-journey-visual-top { border-bottom:1px solid rgba(203,227,229,.14); }
.rg-journey-visual-top>span:first-child { display:flex; align-items:center; gap:9px; }
.rg-journey-map { position:absolute; inset:46px 0 45px; overflow:hidden; opacity:.84; background:#102b38; }
.rg-map-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(134,183,188,.12) 1px,transparent 1px),linear-gradient(90deg,rgba(134,183,188,.12) 1px,transparent 1px);background-size:31px 31px; }
.rg-map-contour { position:absolute;width:110%;height:70%;left:-5%;top:12%;border:1px solid rgba(101,189,175,.24);border-radius:46% 54% 66% 34% / 52% 42% 58% 48%;transform:rotate(-22deg); }
.contour-two { width:91%;height:53%;left:9%;top:26%;border-color:rgba(233,189,92,.3);transform:rotate(23deg); }
.rg-map-road { position:absolute;width:120%;height:14px;left:-10%;top:47%;border-top:2px solid rgba(197,224,223,.35);border-bottom:1px dashed rgba(197,224,223,.22);transform:rotate(-28deg); }
.road-two { top:25%; transform:rotate(37deg); opacity:.62; }
.rg-map-route { position:absolute;left:21%;top:61%;width:60%;height:19%;border-top:2px dashed #59d8b1;border-right:2px dashed #59d8b1;border-radius:0 18px 0 0;transform:rotate(-18deg);filter:drop-shadow(0 0 5px rgba(89,216,177,.6)); }
.rg-map-point { position:absolute;width:11px;height:11px;border:2px solid #9bf4d7;border-radius:50%;background:#0a6257;box-shadow:0 0 0 6px rgba(69,211,165,.16),0 0 20px rgba(69,211,165,.72);left:21%;top:61%; }
.point-two { left:80%;top:41%;border-color:#f5d882;background:#95671b;box-shadow:0 0 0 6px rgba(233,189,92,.14),0 0 18px rgba(233,189,92,.65); }
.point-three { left:53%;top:26%;width:7px;height:7px; }
.rg-map-axis { position:absolute;right:15px;top:13px;color:#81a7ae;font:600 .56rem/1 ui-monospace,monospace;letter-spacing:.08em; }
.axis-y { top:auto;right:auto;left:13px;bottom:15px;writing-mode:vertical-rl;transform:rotate(180deg); }
.rg-journey-mascot { position:absolute;z-index:2;left:10%;right:7%;bottom:48px;display:flex;align-items:end;gap:16px;pointer-events:none; }
.rg-journey-mascot img { width:clamp(168px,19vw,224px);height:auto;max-height:300px;object-fit:contain;object-position:center bottom;filter:drop-shadow(0 16px 16px rgba(0,0,0,.3));animation:rg-jo-hover 4.8s ease-in-out infinite; }
.rg-journey-jo-note { max-width:210px;margin-bottom:22px;padding:11px 13px;border:1px solid rgba(199,228,225,.28);border-radius:5px;background:rgba(7,25,37,.84);backdrop-filter:blur(8px); }
.rg-journey-jo-note span { display:block;margin-bottom:6px;color:#66d4b2;font:700 .57rem/1.2 ui-monospace,monospace;letter-spacing:.12em; }
.rg-journey-jo-note strong { color:#e6eff0;font-size:.81rem;line-height:1.4; }
.rg-journey-visual-foot { position:absolute;inset:auto 0 0;border-top:1px solid rgba(203,227,229,.14); }
.rg-signal-bars { display:flex;align-items:end;gap:3px;height:14px; }
.rg-signal-bars i { width:3px;height:5px;background:#52d2ac;animation:rg-bars 1s ease-in-out infinite alternate; }
.rg-signal-bars i:nth-child(2){height:9px;animation-delay:.15s}.rg-signal-bars i:nth-child(3){height:12px;animation-delay:.3s}.rg-signal-bars i:nth-child(4){height:8px;animation-delay:.45s}.rg-signal-bars i:nth-child(5){height:11px;animation-delay:.6s}
.rg-journey-steps { padding:0 0 6px; }
.rg-journey-step { min-height:clamp(430px,67vh,620px);display:grid;grid-template-columns:54px minmax(0,1fr);gap:12px;align-content:center;padding:35px 0;border-top:1px solid #b9c9cc;opacity:.4;transition:opacity .35s ease; }
.rg-journey-step:last-child { border-bottom:1px solid #b9c9cc; }
.rg-journey-step.is-active { opacity:1; }
.rg-journey-step-no { color:#728991;font:700 .74rem/1.5 ui-monospace,monospace; }
.rg-journey-step.is-active .rg-journey-step-no { color:#087b78; }
.rg-journey-step p { margin:0 0 13px;color:#087b78;font:700 .62rem/1.3 ui-monospace,monospace;letter-spacing:.12em; }
.rg-journey-step h3 { max-width:13ch;margin:0 0 14px;color:#142d3e;font-size:clamp(1.8rem,3.5vw,3.3rem);font-weight:700;line-height:1;letter-spacing:-.055em; }
.rg-journey-step>div>span { display:block;max-width:39ch;color:#586e79;font-size:.97rem;line-height:1.75; }
.rg-journey-step a { display:inline-flex;align-items:center;gap:12px;margin-top:22px;color:#075f65;font-size:.83rem;font-weight:750;text-decoration:none; }
.rg-journey-step a:hover { color:#087f5b; }
.rg-journey-tour { display:inline-flex;align-items:center;gap:10px;margin:25px 0 0;padding:11px 14px;border:1px solid #b9c9cc;border-radius:4px;background:transparent;color:#25404e;font-weight:700; }
.rg-journey-tour>i { color:#087b78; }
.rg-journey-tour span { padding-left:10px;border-left:1px solid #c5d1d2;color:#6b8088;font-size:.73rem;font-weight:500; }
.rg-log { display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px; }
.rg-log-item.rg-announce-card {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    height: auto;
    border-left: 3px solid var(--raniag-primary, #086b78);
}
.rg-log-item time { font-weight:800;font-size:.78rem;letter-spacing:.04em;text-transform:uppercase;color:var(--raniag-primary,#086b78); }
.rg-log-meta { display: flex; align-items: center; gap: 8px; color: #5b6780; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.rg-log-item h3 { margin: 2px 0; font-size: 1rem; font-weight: 800; }
.rg-log-item p { margin: 0; color: #5b6780; font-size: .86rem; }
.rg-home-briefing { position:relative;isolation:isolate;overflow:hidden;margin:clamp(60px,9vw,116px) calc((100vw - min(100vw - 2rem, 1320px))/ -2) 0;padding:clamp(32px,6vw,76px) max(24px, calc((100vw - min(100vw - 2rem, 1320px))/2));background:#0b1c28;color:#e7f1f1; }
.rg-home-briefing::before { content:"";position:absolute;z-index:-1;inset:0;opacity:.42;background:radial-gradient(ellipse at 90% 0%,rgba(11,107,105,.38),transparent 42%),linear-gradient(rgba(145,191,197,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(145,191,197,.06) 1px,transparent 1px);background-size:auto,32px 32px,32px 32px; }
.rg-home-briefing-head { max-width:710px;margin:0 0 34px; }
.rg-home-briefing-head .rg-section-index { color:#a2bbc2; }
.rg-home-briefing-head h2 { margin:0;color:#f4f8f5;font-size:clamp(2.7rem,6vw,5.3rem);font-weight:750;line-height:.96;letter-spacing:-.065em; }
.rg-home-briefing-head h2 em { color:#62d2ba;font-style:normal; }
.rg-home-briefing-head>p:last-child { max-width:45ch;margin:16px 0 0;color:#b9cbd0;line-height:1.65; }
.rg-home-briefing-grid { display:grid;grid-template-columns:minmax(0,1.28fr) minmax(300px,.72fr);align-items:stretch;gap:clamp(18px,3vw,34px); }
.rg-home-briefing-main { display:grid;align-content:start;gap:24px;min-width:0; }
.rg-home-updates,.rg-home-faq { min-width:0;padding:22px 24px;border:1px solid #2d4652;border-radius:7px;background:rgba(16,39,51,.8); }
.rg-home-module-head { display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:17px;padding-bottom:13px;border-bottom:1px solid #344e58; }
.rg-home-module-head h2 { margin:6px 0 0;color:#eef5f3;font-size:1.06rem;font-weight:750; }
.rg-home-module-head>a { color:#7bd9c3;font-size:.78rem;font-weight:700;text-decoration:none; }
.rg-home-module-head>a:hover { color:#c5f5e8; }
.rg-home-live-tag { display:inline-flex;align-items:center;gap:8px;color:#a8c2c8;font:700 .58rem/1 ui-monospace,monospace;letter-spacing:.13em;white-space:nowrap; }
.rg-home-live-tag i,.rg-app-label i { width:7px;height:7px;border-radius:50%;background:#60d8ae;box-shadow:0 0 0 4px rgba(96,216,174,.13); }
.rg-home-empty-update { display:flex;align-items:center;gap:16px;min-height:94px;color:#dce9e9; }
.rg-home-empty-update>.bi { display:grid;place-items:center;width:48px;height:48px;border:1px solid #426370;border-radius:50%;color:#70d7c0;font-size:1.25rem; }
.rg-home-empty-update strong { display:block;font-size:.9rem; }
.rg-home-empty-update p { margin:4px 0 0;color:#a7bec5;font-size:.82rem; }
.rg-home-updates .rg-log { grid-template-columns:1fr; }
.rg-home-updates .rg-log-item.rg-announce-card { padding:15px 0 14px;border:0;border-top:1px solid #344e58;border-radius:0;background:transparent;color:#e9f0f0;box-shadow:none; }
.rg-home-updates .rg-log-item time { color:#72d9c3;font:700 .68rem/1.3 ui-monospace,monospace; }
.rg-home-updates .rg-log-item p,.rg-home-updates .rg-log-meta { color:#a9bec4; }
.rg-home-updates .rg-log-item h3 { color:#eff6f4;font-size:.92rem; }
.rg-home-updates .rg-announce-badge,.rg-home-faq .rg-announce-badge { color:#88dfcc; }
.rg-home-faq .rg-faq-accordion .accordion-item { border:1px solid #304955!important;border-radius:4px!important;background:#102631; }
.rg-home-faq .rg-faq-accordion .accordion-button { padding:14px 15px;background:#102631;color:#e6f0ef;font-size:.85rem; }
.rg-home-faq .rg-faq-accordion .accordion-button:not(.collapsed) { background:#173b40;color:#8ce0ce; }
.rg-home-faq .rg-faq-accordion .accordion-body { padding:14px 15px;color:#b8cbd0;font-size:.84rem;line-height:1.6; }
.rg-home-faq .rg-faq-accordion .accordion-button::after { filter:brightness(0) saturate(100%) invert(75%) sepia(26%) saturate(714%) hue-rotate(113deg); }
.rg-home-aside { display:grid;grid-template-rows:minmax(310px,1.15fr) minmax(245px,.85fr);gap:14px;min-width:0; }
.rg-download-cta,.rg-home-support-card { position:relative;isolation:isolate;overflow:hidden;margin:0;border:1px solid #3a5663;border-radius:7px;background:#102b38;color:#e7f1f1; }
.rg-download-cta { min-height:330px;padding:22px; }
.rg-download-cta::before { content:"";position:absolute;z-index:-1;inset:0;background:linear-gradient(140deg,rgba(10,91,92,.38),transparent 58%),linear-gradient(rgba(141,197,203,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(141,197,203,.06) 1px,transparent 1px);background-size:auto,28px 28px,28px 28px; }
.rg-app-label,.rg-app-index { display:flex;align-items:center;gap:8px;color:#a9c4ca;font:700 .58rem/1.25 ui-monospace,monospace;letter-spacing:.13em; }
.rg-app-orbit { position:absolute;right:-28px;top:39px;width:195px;aspect-ratio:1;border:1px solid rgba(94,211,187,.34);border-radius:50%;animation:rg-orbit 24s linear infinite; }
.rg-app-orbit::before,.rg-app-orbit::after { content:"";position:absolute;inset:15%;border:1px dashed rgba(170,217,214,.24);border-radius:50%; }
.rg-app-orbit::after { inset:34%;border-style:solid;border-color:rgba(233,189,92,.34); }
.rg-app-orbit span,.rg-app-orbit i,.rg-app-orbit b { position:absolute;display:block;width:7px;height:7px;border-radius:50%;background:#69ddba;box-shadow:0 0 14px #69ddba;top:14%;left:54%; }
.rg-app-orbit i { top:68%;left:78%;background:#f2ca6e;box-shadow:0 0 14px #f2ca6e; }
.rg-app-orbit b { top:48%;left:8%; }
.rg-download-cta>img { position:absolute;right:12px;bottom:-8px;z-index:0;width:clamp(150px,17vw,210px);height:auto;max-height:255px;object-fit:contain;filter:drop-shadow(0 12px 18px rgba(0,0,0,.3));animation:rg-jo-hover 4.8s ease-in-out infinite; }
.rg-app-copy { position:relative;z-index:1;width:64%;margin-top:34px; }
.rg-app-index { margin-bottom:11px;color:#6ad4bb; }
.rg-app-copy h2 { max-width:12ch;margin:0;color:#f0f6f3;font-size:clamp(1.35rem,2vw,1.9rem);font-weight:750;line-height:1.05;letter-spacing:-.04em; }
.rg-app-copy p { max-width:24ch;margin:10px 0 18px;color:#b7c9cd;font-size:.81rem;line-height:1.5; }
.rg-install-button,.rg-home-support-button { display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:43px;padding:0 14px;border:1px solid #5cc6ae;border-radius:4px;background:#0d7775;color:#fff;font-size:.77rem;font-weight:750;text-decoration:none;transition:background .2s ease,transform .2s ease,border-color .2s ease; }
.rg-install-button:hover,.rg-home-support-button:hover { transform:translateY(-2px);border-color:#9cebd9;background:#0b9187;color:#fff; }
.rg-install-button .bi:last-child { margin-left:3px;font-size:.7rem; }
.rg-home-support-card { display:grid;align-content:start;justify-items:start;padding:22px; }
.rg-support-orbit { position:absolute;right:22px;top:17px;display:grid;place-items:center;width:44px;height:44px;border:1px solid #426371;border-radius:50%;color:#77dbc5;font-size:1.1rem; }
.rg-home-support-card h2 { max-width:18ch;margin:14px 0 7px;color:#f1f6f3;font-size:1.2rem;font-weight:750;line-height:1.15; }
.rg-home-support-card p { max-width:38ch;margin:0 0 16px;color:#afc3c8;font-size:.8rem;line-height:1.55; }
.rg-home-support-button { min-height:42px; }
@keyframes rg-jo-hover { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-8px); } }
@keyframes rg-orbit { to { transform:rotate(360deg); } }
@keyframes rg-bars { to { opacity:.35;transform:scaleY(.6);transform-origin:bottom; } }
@media(max-width:991.98px) {
    .rg-home-hero { min-height:560px; }
    .rg-home-hero-content { width:min(700px,82%); }
    .rg-home-orbit { right:9%;top:12%; }
    .rg-journey-layout { grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:24px; }
    .rg-journey-visual { top:calc(50vh - 220px);min-height:440px; }
    .rg-journey-mascot img { width:156px; }
    .rg-home-briefing-grid { grid-template-columns:1fr; }
    .rg-home-aside { grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:1fr; }
    .rg-download-cta { min-height:320px; }
}
@media(max-width:767.98px) {
    .rg-home-hero { min-height:600px;margin-inline:-.75rem;padding:44px 24px;align-items:center; }
    .rg-home-hero-photo { background-position:62% center;background-image:linear-gradient(90deg,rgba(7,20,31,.93),rgba(7,20,31,.65) 66%,rgba(7,20,31,.4)),linear-gradient(0deg,rgba(7,20,31,.38),transparent 70%),url('/images/pamplona-landmark.jpg'); }
    .rg-home-hero-content { width:100%;padding:0 0 52px; }
    .rg-home-hero h1 { margin-top:27px;font-size:clamp(3.1rem,13vw,5rem); }
    .rg-home-orbit { top:auto;bottom:11%;right:9%;width:112px;opacity:.5; }
    .rg-home-coordinate { right:15px;bottom:15px;padding:8px 10px;font-size:.56rem; }
    .rg-home-shortcuts { grid-template-columns:1fr;margin-top:14px; }
    .rg-home-shortcuts>a { min-height:75px;grid-template-columns:25px 24px minmax(0,1fr) 18px;padding:11px 8px; }
    .rg-home-shortcuts>a+a { border-left:0;border-top:1px solid #cbd6d7; }
    .rg-report-journey { padding-top:70px; }
    .rg-journey-heading { margin-bottom:24px; }
    .rg-journey-layout { grid-template-columns:1fr;gap:8px; }
    .rg-journey-visual { position:relative;top:auto;min-height:420px; }
    .rg-journey-mascot img { width:178px; }
    .rg-journey-step { min-height:0;grid-template-columns:39px 1fr;gap:9px;padding:44px 0 52px;opacity:1; }
    .rg-journey-step h3 { font-size:clamp(1.85rem,8vw,2.8rem); }
    .rg-journey-tour { width:100%;justify-content:center;flex-wrap:wrap; }
    .rg-home-briefing { margin-inline:-.75rem;padding-inline:20px; }
    .rg-home-briefing-grid { gap:14px; }
    .rg-home-briefing-main { gap:14px; }
    .rg-home-updates,.rg-home-faq { padding:17px 15px; }
    .rg-home-aside { grid-template-columns:1fr; }
    .rg-download-cta { min-height:310px; }
}
@media(max-width:420px) {
    .rg-home-hero { min-height:570px;padding-inline:20px; }
    .rg-home-actions { align-items:flex-start;flex-direction:column;gap:16px; }
    .rg-home-primary-action { min-height:50px; }
    .rg-journey-visual { min-height:365px; }
    .rg-journey-mascot { left:5%;right:4%; }
    .rg-journey-mascot img { width:142px; }
    .rg-journey-jo-note { max-width:170px;padding:9px;margin-bottom:15px; }
    .rg-home-module-head { align-items:flex-start; }
    .rg-home-live-tag { padding-top:5px; }
    .rg-download-cta>img { right:-4px;width:158px; }
    .rg-app-copy { width:67%; }
}
@media (prefers-reduced-motion: reduce) {
    .rg-home-orbit span,.rg-signal-bars i,.rg-app-orbit,.rg-download-cta>img,.rg-journey-mascot img { animation:none; }
    .rg-home-hero-photo { transform:none; }
    .rg-cursor-field { display:none; }
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
        const feedback = document.getElementById('rg-install-feedback');
        if (rgDeferredPrompt) {
            rgDeferredPrompt.prompt();
            const choice = await rgDeferredPrompt.userChoice;
            rgDeferredPrompt = null;
            if (feedback) {
                feedback.textContent = choice.outcome === 'accepted'
                    ? 'RANIAG is being added to your device.'
                    : 'Install cancelled. You can continue using the website in your browser.';
            }
        } else {
            if (feedback) {
                feedback.textContent = window.matchMedia('(display-mode: standalone)').matches
                    ? 'RANIAG is already running as an installed app.'
                    : 'Open your browser menu and choose “Install app” or “Add to Home Screen”.';
            }
        }
    });
</script>
@endpush
@endsection
