@extends('layouts.public')

@section('title', 'Track Report')

@section('content')
<div class="container rg-track-page">
    <section class="rg-case" aria-labelledby="rg-track-title" data-rg-reveal>
        <div class="rg-case-board">
            <div class="rg-case-radar" aria-hidden="true">
                <span class="rg-case-sweep"></span>
                <span class="rg-case-blip"></span>
                <img src="/images/icons/raniag-master.svg" alt="" width="28" height="28">
            </div>
            <p class="rg-case-kicker"><span></span>{{ config('raniag.organization') }} · CASE DESK</p>
            <h1 id="rg-track-title">Your report.<br><em>In clear view.</em></h1>
            <p class="rg-case-lead">A tracking number is your private window into the response. Enter the reference from your receipt to see its latest movement.</p>
            <div class="rg-case-map-art" aria-hidden="true">
                <span class="rg-case-map-grid"></span><i class="rg-case-route"></i><i class="rg-case-locator"></i>
                <img src="/images/guide/jo-map.png?v=1" alt="" width="220" height="294">
                <span class="rg-case-coordinates">17° 27′ N <i></i> 121° 28′ E<br><b>PAMPLONA / CAGAYAN</b></span>
            </div>
            <div class="rg-case-status-label"><i></i> REPORT STATUS · LIVE LOOKUP</div>
            <ol class="rg-case-path" aria-label="How a report moves after it is filed">
                <li>
                    <i class="bi bi-send"></i>
                    <div>
                        <strong>Submitted</strong>
                        <span>Logged. Waiting for review.</span>
                    </div>
                </li>
                <li>
                    <i class="bi bi-person-check"></i>
                    <div>
                        <strong>Assigned</strong>
                        <span>Sent to the responder for that area.</span>
                    </div>
                </li>
                <li>
                    <i class="bi bi-arrow-repeat"></i>
                    <div>
                        <strong>In progress</strong>
                        <span>A team is working the scene.</span>
                    </div>
                </li>
                <li>
                    <i class="bi bi-check-circle"></i>
                    <div>
                        <strong>Resolved</strong>
                        <span>Closed, with the outcome on this page.</span>
                    </div>
                </li>
            </ol>
        </div>

        <form class="rg-case-ticket" action="{{ route('public.track.lookup') }}" method="POST">
            @csrf
            <div class="rg-case-ticket-head">
                <span class="rg-case-ticket-index">01 / LOOKUP</span>
                <span class="rg-case-ticket-signal"><i></i> PRIVATE CASE PAGE</span>
            </div>
            <label for="tracking_number">Tracking reference</label>
            <p class="rg-case-ticket-help">Enter the number shown on your report receipt.</p>
            <input type="text"
                   class="@error('tracking_number') is-invalid @enderror"
                   id="tracking_number"
                   name="tracking_number"
                   value="{{ old('tracking_number', $prefillTrackingNumber ?? '') }}"
                   placeholder="RAN-XXXXXX"
                   required
                   autocomplete="off"
                   spellcheck="false"
                   inputmode="text">
            @error('tracking_number')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <p class="rg-case-hint"><i class="bi bi-info-circle" aria-hidden="true"></i> Keep the dashes; letter case does not matter.</p>
            <button type="submit">
                <i class="bi bi-arrow-right-circle" aria-hidden="true"></i> Open case updates
            </button>
            <ul class="rg-case-notes">
                <li><i class="bi bi-shield-lock" aria-hidden="true"></i> No account required</li>
                <li><i class="bi bi-clock-history" aria-hidden="true"></i> Plain-language updates</li>
            </ul>
            <p class="rg-case-lost">
                Lost the number? <a href="{{ route('public.support') }}">Contact the support team</a> or
                <a href="{{ route('public.report.create') }}">file a new report</a>.
            </p>
        </form>
    </section>
</div>
@endsection

@push('styles')
<style>
.rg-case {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(330px, .8fr);
    min-height: min(720px, calc(100vh - 190px));
    border: 1px solid #294754;
    border-radius: 6px;
    overflow: hidden;
    background: #0b1b25;
    box-shadow: 0 26px 70px -46px rgba(8, 23, 33, .9);
}
.rg-case-board {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    padding: clamp(28px,4vw,52px);
    color: #e8eef8;
    background:
        radial-gradient(ellipse at 80% 20%, rgba(16, 104, 98, .3), transparent 42%),
        linear-gradient(rgba(135,191,196,.055) 1px, transparent 1px),
        linear-gradient(90deg, rgba(135,191,196,.055) 1px, transparent 1px),
        linear-gradient(145deg, #102b39 0%, #0a1823 72%);
    background-size:auto,32px 32px,32px 32px,auto;
}
.rg-case-board::after { content:"";position:absolute;z-index:-1;right:-155px;top:12%;width:430px;aspect-ratio:1;border:1px solid rgba(93,203,181,.18);border-radius:50%;box-shadow:0 0 0 35px rgba(93,203,181,.035),0 0 0 80px rgba(93,203,181,.025); }
.rg-case-radar {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    position: relative;
    overflow: hidden;
    margin-bottom: 18px;
    background:radial-gradient(circle,rgba(46,177,151,.25) 0 32%,transparent 33%),repeating-radial-gradient(circle,rgba(151,210,204,.47) 0 1px,transparent 1px 14px);
    border: 1px solid rgba(118,199,188,.4);
}
.rg-case-sweep {
    position: absolute;
    inset: 0;
    background: conic-gradient(from 0deg, transparent 0 70%, rgba(96, 215, 178, .2) 86%, rgba(191, 247, 226, .9) 100%);
    animation: rg-case-sweep 2.4s linear infinite;
}
.rg-case-blip {
    position: absolute;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #67dfb1;
    right: 16px;
    top: 22px;
    box-shadow: 0 0 0 0 rgba(103, 223, 177, .7);
    animation: rg-case-blip 2.4s ease-out infinite;
}
.rg-case-radar img {
    position: absolute;
    left: 50%;
    top: 50%;
    width: 26px;
    height: 26px;
    margin: -13px 0 0 -13px;
}
.rg-case-kicker {
    position:relative;z-index:2;
    display:flex;align-items:center;gap:9px;margin: 0 0 13px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: #82c4c4;
}
.rg-case-kicker>span,.rg-case-status-label>i { width:7px;height:7px;border-radius:50%;background:#5dd6aa;box-shadow:0 0 0 4px rgba(93,214,170,.13); }
.rg-case-board h1 {
    position:relative;z-index:2;margin:0 0 12px;max-width:11ch;color:#f1f6f3;
    font-size:clamp(2.8rem,5vw,5.8rem);font-weight:760;line-height:.9;letter-spacing:-.07em;
}
.rg-case-board h1 em { color:#63d1b8;font-style:normal; }
.rg-case-lead { position:relative;z-index:2;margin:0 0 22px;max-width:min(35rem,62%);color:#bacbd0;line-height:1.65; }
.rg-case-map-art { position:absolute;z-index:1;right:1%;top:17%;width:43%;height:42%;pointer-events:none; }
.rg-case-map-grid { position:absolute;inset:0;border:1px solid rgba(127,190,193,.2);background:linear-gradient(30deg,transparent 47%,rgba(146,203,200,.19) 48% 49%,transparent 50%),linear-gradient(-24deg,transparent 55%,rgba(146,203,200,.13) 56% 57%,transparent 58%);mask-image:radial-gradient(ellipse,#000 25%,transparent 75%); }
.rg-case-map-art>img { position:absolute;right:2%;bottom:-12%;height:118%;width:auto;filter:drop-shadow(0 12px 18px rgba(0,0,0,.34));animation:rg-case-jo-float 5s ease-in-out infinite; }
.rg-case-route { position:absolute;left:12%;top:57%;width:72%;height:25%;border-top:2px dashed #65d8b7;border-right:2px dashed #65d8b7;border-radius:0 18px 0 0;transform:rotate(-19deg);filter:drop-shadow(0 0 6px rgba(101,216,183,.6)); }
.rg-case-locator { position:absolute;left:30%;top:46%;width:12px;height:12px;border:2px solid #d9ffe9;border-radius:50%;background:#0b806c;box-shadow:0 0 0 7px rgba(65,215,164,.18),0 0 18px #45d7a4;animation:rg-case-blip 2.4s ease-out infinite; }
.rg-case-coordinates { position:absolute;right:0;bottom:0;padding:9px 10px;border:1px solid rgba(196,224,222,.25);background:rgba(8,25,35,.75);color:#d8e8e7;font:700 .57rem/1.45 ui-monospace,monospace;letter-spacing:.07em; }
.rg-case-coordinates>i { display:inline-block;width:18px;height:1px;margin:0 4px;background:#e9bd5c;vertical-align:middle; }
.rg-case-coordinates b { color:#77cbb9;font-size:.5rem;letter-spacing:.14em; }
.rg-case-status-label { position:relative;z-index:2;display:flex;align-items:center;gap:9px;max-width:62%;margin:22px 0 10px;color:#9ab7be;font:700 .61rem/1.2 ui-monospace,monospace;letter-spacing:.13em; }
.rg-case-path {
    position:relative;
    z-index:2;
    max-width:62%;
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0;
    position: relative;
}
.rg-case-path::before {
    content: "";
    position: absolute;
    left: 19px;
    top: 12px;
    bottom: 12px;
    width: 2px;
    background: rgba(103, 206, 187, .25);
}
.rg-case-path li {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    padding: 8px 0;
}
.rg-case-path i {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #183b46;
    border: 1px solid rgba(147, 214, 201, .28);
    color: #a9e5d4;
    position: relative;
    z-index: 1;
}
.rg-case-path li.is-live i {
    background: #0a827b;
    border-color: #84e5cd;
    color: #fff;
    box-shadow: 0 0 0 6px rgba(11, 130, 123, .25);
}
.rg-case-path strong { display: block; color: #fff; }
.rg-case-path span { color: #9aabc2; font-size: .86rem; }
.rg-case-status-label~.rg-case-path li { transition:transform .25s ease; }
.rg-case-status-label~.rg-case-path li.is-live { transform:translateX(5px); }
.rg-case-path li.is-live span { color:#c3d6d7; }
.rg-case-ticket {
    position:relative;display:flex;flex-direction:column;justify-content:center;margin:0;padding:clamp(28px,4vw,50px);
    border-left:1px solid #31505a;background:radial-gradient(ellipse at 100% 0,rgba(19,103,101,.23),transparent 48%),linear-gradient(150deg,#112a36,#0b1a24);color:#e8f0ef;
}
.rg-case-ticket-head {
    display: flex;
    justify-content: space-between;
    align-items:center;gap:12px;margin-bottom:38px;padding-bottom:14px;border-bottom:1px dashed #45606a;
}
.rg-case-ticket-index,.rg-case-ticket-signal { color:#78d5c0;font:700 .59rem/1.2 ui-monospace,monospace;letter-spacing:.13em; }
.rg-case-ticket-signal { display:inline-flex;align-items:center;gap:7px;color:#a9bec3;font-size:.52rem; }
.rg-case-ticket-signal i { width:6px;height:6px;border-radius:50%;background:#65d9ae; }
.rg-case-ticket label {
    color:#eaf3f1;font-size:.8rem;font-weight:750;letter-spacing:.04em;
}
.rg-case-ticket-help { margin:6px 0 8px;color:#a9bec3;font-size:.82rem;line-height:1.5; }
.rg-case-ticket input {
    width: 100%;
    margin-top: 6px;
    border: 0;
    border:1px solid #45616c;border-radius:4px;background:#0c202b;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 1.55rem;
    letter-spacing: .12em;
    padding: .35rem 0 .5rem;
    color:#f0f7f5;padding:.65rem .75rem;
}
.rg-case-ticket input:focus { outline:0;border-color:#60cfb6;box-shadow:0 0 0 3px rgba(96,207,182,.16); }
.rg-case-ticket input.is-invalid { border-color:#ef8d79; }
.rg-case-hint { display:flex;align-items:flex-start;gap:7px;margin:9px 0 18px;color:#a9bec3;font-size:.76rem;line-height:1.45; }
.rg-case-hint>.bi { color:#74cdbb; }
.rg-case-ticket button {
    border:1px solid #53c4ae;border-radius:4px;background:#087b78;
    color: #fff;
    font-weight: 800;
    min-height: 50px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.rg-case-ticket button:hover { background:#0a9187;transform:translateY(-1px); }
.rg-case-notes {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 12px 16px;
    margin: 14px 0 0;
    padding: 0;
    color:#a9bec3;
    font-size: .82rem;
    font-weight: 700;
}
.rg-case-lost { margin:18px 0 0;color:#a9bec3;font-size:.79rem;line-height:1.6; }
.rg-case-lost a { color:#75dbc3;font-weight:700; }
.rg-case-lost a:hover { color:#c0f3e5; }
@keyframes rg-case-jo-float { 0%,100% { transform:translateY(0); } 50% { transform:translateY(-7px); } }
@keyframes rg-case-sweep { to { transform: rotate(360deg); } }
@keyframes rg-case-blip {
    0% { box-shadow: 0 0 0 0 rgba(134, 239, 172, .65); }
    70% { box-shadow: 0 0 0 10px rgba(134, 239, 172, 0); }
    100% { box-shadow: 0 0 0 0 rgba(134, 239, 172, 0); }
}
@media (max-width: 991.98px) {
    .rg-case { grid-template-columns:1fr;min-height:0; }
    .rg-case-board { padding:clamp(24px,5vw,42px); }
    .rg-case-map-art { right:3%;top:10%;width:38%;height:46%; }
    .rg-case-board h1 { max-width:11ch; }
    .rg-case-ticket { border-left:0;border-top:1px solid #31505a; }
}
@media (prefers-reduced-motion: reduce) {
    .rg-case-sweep,.rg-case-blip,.rg-case-map-art>img { animation:none; }
    .rg-case-ticket button:hover,.rg-case-path li.is-live { transform:none; }
}
@media (max-width:575.98px) {
    .rg-case-board { padding:24px 18px 28px; }
    .rg-case-radar { width:64px;height:64px; }
    .rg-case-map-art { top:auto;right:-8%;bottom:57px;width:43%;height:21%;opacity:.42; }
    .rg-case-map-art>img { height:115%; }
    .rg-case-board h1 { max-width:10ch;font-size:clamp(2.5rem,12vw,3.6rem); }
    .rg-case-lead { max-width:100%;padding-right:0;font-size:.91rem; }
    .rg-case-status-label,.rg-case-path { max-width:100%; }
    .rg-case-status-label { margin-top:24px; }
    .rg-case-path span { font-size:.78rem; }
    .rg-case-ticket { padding:27px 20px; }
    .rg-case-ticket-head { margin-bottom:28px; }
    .rg-case-ticket input { font-size:1.2rem; }
}
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const steps = document.querySelectorAll('.rg-case-path li');
        if (steps.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            steps[0]?.classList.add('is-live');
            return;
        }
        let index = 0;
        const tick = () => {
            steps.forEach((step, i) => step.classList.toggle('is-live', i === index));
            index = (index + 1) % steps.length;
        };
        tick();
        window.setInterval(tick, 1800);
    })();
</script>
@endpush
