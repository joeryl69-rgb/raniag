@extends('layouts.public')
@section('title', 'Support Center')
@section('content')
<div class="container rg-support-page">
    <header class="rg-support-intro" data-rg-reveal>
        <div class="rg-support-intro-copy">
            <p class="rg-support-intro-index"><span></span> MDRRMO PAMPLONA <i>/</i> HELP DESK</p>
            <h1>Let’s get you<br><em>to the right place.</em></h1>
            <p>If you need to file a new emergency incident, use the incident report. For website issues, questions, or follow-up concerns, send a note to the support team.</p>
            <a href="{{ route('public.report.create') }}" class="rg-support-emergency-link"><i class="bi bi-plus-circle" aria-hidden="true"></i> File a new incident <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
        <figure class="rg-support-guide">
            <span class="rg-support-guide-orbit" aria-hidden="true"></span>
            <img src="/images/guide/jo-phone.png?v=1" alt="JO, the RANIAG support guide" width="170" height="227">
            <figcaption><span>JO · SUPPORT GUIDE</span><strong>Tell us what you need help with.</strong></figcaption>
        </figure>
        <a href="{{ route('public.home') }}" class="rg-support-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back home</a>
    </header>
    <div class="rg-support-form-intro">
        <span><i class="bi bi-chat-square-text" aria-hidden="true"></i> MESSAGE THE TEAM</span>
        <p>Choose a topic and give us enough detail to route your message.</p>
    </div>
    <x-support-form :action="route('public.feedback.store')" :categories="$categories" :lock-identity="false" :back-url="route('public.home')" />
</div>
@endsection

@push('styles')
<style>
.rg-support-page { max-width:1210px; }
.rg-support-intro { position:relative;display:grid;grid-template-columns:minmax(0,1fr) 270px;align-items:center;min-height:320px;overflow:hidden;margin:0 0 30px;padding:clamp(27px,5vw,58px);border:1px solid #20414f;border-radius:7px;background:linear-gradient(112deg,#0b1c2a 0%,#0c2634 70%,#113942 100%);color:#edf5f4; }
.rg-support-intro::before { content:"";position:absolute;inset:0;pointer-events:none;opacity:.32;background-image:linear-gradient(rgba(170,220,216,.11) 1px,transparent 1px),linear-gradient(90deg,rgba(170,220,216,.11) 1px,transparent 1px);background-size:32px 32px;mask-image:linear-gradient(90deg,#000,transparent 84%); }
.rg-support-intro-copy { position:relative;z-index:1;max-width:660px; }
.rg-support-intro-index { display:flex;align-items:center;gap:9px;margin:0 0 20px;color:#a7c3c9;font:700 .66rem/1.2 ui-monospace,monospace;letter-spacing:.14em; }
.rg-support-intro-index span { width:8px;height:8px;border-radius:50%;background:#58d2ac;box-shadow:0 0 0 4px rgba(88,210,172,.15); }
.rg-support-intro-index i { color:#e9bd5c;font-style:normal; }
.rg-support-intro h1 { margin:0;color:#f4f7f3;font-size:clamp(2.6rem,6vw,5rem);font-weight:750;line-height:.95;letter-spacing:-.07em; }
.rg-support-intro h1 em { color:#65cbb5;font-style:normal; }
.rg-support-intro-copy>p:not(.rg-support-intro-index) { max-width:54ch;margin:18px 0 0;color:#c4d4d7;font-size:.98rem;line-height:1.65; }
.rg-support-emergency-link { display:inline-flex;align-items:center;gap:10px;margin-top:23px;color:#f3d17e;font-size:.85rem;font-weight:700;text-decoration:none; }
.rg-support-emergency-link:hover { color:#fff0be; }
.rg-support-guide { position:relative;z-index:1;display:grid;justify-items:center;align-self:stretch;align-content:end;margin:0; }
.rg-support-guide-orbit { position:absolute;top:22px;right:18px;width:200px;aspect-ratio:1;border:1px solid rgba(91,213,180,.45);border-radius:50%; }
.rg-support-guide-orbit::before,.rg-support-guide-orbit::after { content:"";position:absolute;inset:18%;border:1px dashed rgba(199,228,225,.28);border-radius:50%; }
.rg-support-guide-orbit::after { inset:37%;border-style:solid;border-color:rgba(233,189,92,.48); }
.rg-support-guide img { position:relative;z-index:1;width:190px;height:auto;max-height:250px;object-fit:contain;filter:drop-shadow(0 12px 16px rgba(0,0,0,.28));animation:rg-support-jo-hover 4.8s ease-in-out infinite; }
.rg-support-guide figcaption { position:relative;z-index:2;display:grid;gap:4px;max-width:225px;margin-top:-8px;padding:10px 13px;border:1px solid rgba(203,227,229,.24);border-radius:4px;background:rgba(8,24,34,.86);text-align:left; }
.rg-support-guide figcaption span { color:#69d5bb;font:700 .57rem/1.2 ui-monospace,monospace;letter-spacing:.1em; }
.rg-support-guide figcaption strong { color:#e8f1f0;font-size:.8rem; }
.rg-support-back { position:absolute;z-index:2;top:20px;right:20px;display:inline-flex;align-items:center;gap:7px;color:#d7e5e5;font-size:.76rem;font-weight:700;text-decoration:none; }
.rg-support-back:hover { color:#72ddc3; }
.rg-support-form-intro { display:flex;align-items:baseline;justify-content:space-between;gap:18px;margin:0 0 14px;padding-bottom:12px;border-bottom:1px solid #bdcbce; }
.rg-support-form-intro span { color:#087b78;font:700 .67rem/1.2 ui-monospace,monospace;letter-spacing:.12em; }
.rg-support-form-intro p { margin:0;color:#596f79;font-size:.84rem; }
.rg-support-page .rg-support-card { border-radius:6px; }
.rg-support-page .rg-support-card__head--dark { border-radius:6px 6px 0 0;background:#163542; }
.rg-support-page .rg-support-submit { display:inline-flex;align-items:center;justify-content:center;min-height:44px;border-radius:4px;background:#087b78;box-shadow:none; }
.rg-support-page .rg-support-submit:hover { background:#096760;opacity:1; }
.rg-support-page .rg-cat-choice { border-radius:5px; }
.rg-support-page .rg-info-box { border-radius:5px; }
html[data-public-theme="dark"] .rg-support-form-intro { border-color:#38525e; }
html[data-public-theme="dark"] .rg-support-form-intro p { color:#a9bdc3; }
html[data-public-theme="dark"] .rg-support-page .rg-support-card { background:#142630;border-color:#304955;color:#e3ecee; }
html[data-public-theme="dark"] .rg-support-page .rg-cat-choice { background:#10232d;border-color:#47606b; }
html[data-public-theme="dark"] .rg-support-page .rg-cat-choice__label { color:#e3ecee; }
html[data-public-theme="dark"] .rg-support-page .rg-cat-choice__hint { color:#a9bdc3; }
html[data-public-theme="dark"] .rg-support-page .rg-cat-choice.is-selected { background:#163f43;border-color:#43c3aa; }
@media(max-width:767.98px) {
    .rg-support-intro { grid-template-columns:1fr;min-height:0;padding:34px 24px 24px; }
    .rg-support-intro-copy { padding-right:0; }
    .rg-support-intro h1 { font-size:clamp(2.7rem,12vw,4.5rem); }
    .rg-support-guide { grid-template-columns:100px minmax(0,1fr);justify-items:start;align-items:center;align-content:start;gap:0 10px;margin:22px 0 8px; }
    .rg-support-guide-orbit { top:0;right:auto;left:0;width:112px; }
    .rg-support-guide img { width:110px;max-height:148px; }
    .rg-support-guide figcaption { max-width:none;margin:0;padding:9px 10px; }
    .rg-support-guide figcaption strong { font-size:.74rem; }
    .rg-support-back { top:18px;right:18px;bottom:auto; }
    .rg-support-form-intro { align-items:flex-start;flex-direction:column;gap:7px; }
}
@keyframes rg-support-jo-hover { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-7px); } }
@media(prefers-reduced-motion:reduce) { .rg-support-guide img { animation:none; } }
</style>
@endpush
