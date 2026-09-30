@extends('layouts.public')

@section('title', 'Track Report')

@section('content')
<div class="container">
    <section class="rg-case" aria-labelledby="rg-track-title">
        <div class="rg-case-board">
            <div class="rg-case-radar" aria-hidden="true">
                <span class="rg-case-sweep"></span>
                <span class="rg-case-blip"></span>
                <img src="/images/icons/raniag-master.svg" alt="" width="28" height="28">
            </div>
            <p class="rg-case-kicker">{{ config('raniag.organization') }} · Incident desk</p>
            <h1 id="rg-track-title">Find where your report stands</h1>
            <p class="rg-case-lead">The number on your receipt is the case. Enter it and the desk shows submitted, assigned, in progress, or resolved.</p>
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
            <div class="rg-case-stub">
                <span>Case lookup</span>
                <span>Pamplona</span>
            </div>
            <label for="tracking_number">Tracking number</label>
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
            <p class="rg-case-hint">Same dashes as on the receipt. Capital letters are optional.</p>
            <button type="submit">
                <i class="bi bi-search"></i> Open this case
            </button>
            <ul class="rg-case-notes">
                <li><i class="bi bi-shield-lock"></i> No account</li>
                <li><i class="bi bi-clock-history"></i> Plain-language updates</li>
            </ul>
            <p class="rg-case-lost">
                Lost the number? Contact {{ config('raniag.organization') }}, or
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
    grid-template-columns: minmax(0, 1.15fr) minmax(280px, 420px);
    min-height: calc(100vh - 220px);
    border-radius: 24px;
    overflow: hidden;
    background: #0f1c33;
    box-shadow: 0 24px 50px -28px rgba(8, 15, 28, .85);
}
.rg-case-board {
    position: relative;
    padding: 36px 36px 28px;
    color: #e8eef8;
    background:
        radial-gradient(circle at 18% 20%, rgba(37, 99, 235, .28), transparent 36%),
        linear-gradient(160deg, #12243f 0%, #0b1220 70%);
}
.rg-case-radar {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    position: relative;
    overflow: hidden;
    margin-bottom: 18px;
    background:
        radial-gradient(circle, rgba(37, 99, 235, .25) 0 32%, transparent 33%),
        repeating-radial-gradient(circle, rgba(147, 197, 253, .55) 0 1px, transparent 1px 14px);
    border: 1px solid rgba(147, 197, 253, .35);
}
.rg-case-sweep {
    position: absolute;
    inset: 0;
    background: conic-gradient(from 0deg, transparent 0 70%, rgba(96, 165, 250, .2) 86%, rgba(191, 219, 254, .9) 100%);
    animation: rg-case-sweep 2.4s linear infinite;
}
.rg-case-blip {
    position: absolute;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #86efac;
    right: 16px;
    top: 22px;
    box-shadow: 0 0 0 0 rgba(134, 239, 172, .7);
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
    margin: 0 0 6px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: #93c5fd;
}
.rg-case-board h1 {
    margin: 0 0 8px;
    max-width: 16ch;
    font-size: clamp(1.8rem, 3vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -.02em;
    color: #fff;
}
.rg-case-lead { margin: 0 0 22px; max-width: 36rem; color: #b7c3d6; }
.rg-case-path {
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
    background: rgba(147, 197, 253, .22);
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
    background: #173055;
    border: 1px solid rgba(147, 197, 253, .35);
    color: #bfdbfe;
    position: relative;
    z-index: 1;
}
.rg-case-path li.is-live i {
    background: #0b5ed7;
    border-color: #93c5fd;
    color: #fff;
    box-shadow: 0 0 0 6px rgba(11, 94, 215, .25);
}
.rg-case-path strong { display: block; color: #fff; }
.rg-case-path span { color: #9aabc2; font-size: .86rem; }
.rg-case-ticket {
    margin: 18px;
    padding: 22px 22px 18px;
    border-radius: 18px;
    background:
        radial-gradient(circle at 0 18px, transparent 8px, #fff 9px) left center / 18px 28px repeat-y,
        #fff;
    background-position: -9px 0, 0 0;
    color: #1e2b33;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.rg-case-stub {
    display: flex;
    justify-content: space-between;
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1px dashed #c5d0dc;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: #0b5ed7;
}
.rg-case-ticket label {
    font-size: .78rem;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #5b6780;
}
.rg-case-ticket input {
    width: 100%;
    margin-top: 6px;
    border: 0;
    border-bottom: 2px solid #0f1c33;
    border-radius: 0;
    background: transparent;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 1.55rem;
    letter-spacing: .12em;
    padding: .35rem 0 .5rem;
    color: #0f1c33;
}
.rg-case-ticket input:focus { outline: none; border-bottom-color: #0b5ed7; }
.rg-case-ticket input.is-invalid { border-bottom-color: #dc3545; }
.rg-case-hint { margin: 8px 0 16px; color: #6b778c; font-size: .82rem; }
.rg-case-ticket button {
    border: 0;
    border-radius: 12px;
    background: #0b5ed7;
    color: #fff;
    font-weight: 800;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.rg-case-ticket button:hover { background: #084298; }
.rg-case-notes {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    gap: 12px 16px;
    margin: 14px 0 0;
    padding: 0;
    color: #5b6780;
    font-size: .82rem;
    font-weight: 700;
}
.rg-case-lost { margin: 12px 0 0; color: #6b778c; font-size: .82rem; }
@keyframes rg-case-sweep { to { transform: rotate(360deg); } }
@keyframes rg-case-blip {
    0% { box-shadow: 0 0 0 0 rgba(134, 239, 172, .65); }
    70% { box-shadow: 0 0 0 10px rgba(134, 239, 172, 0); }
    100% { box-shadow: 0 0 0 0 rgba(134, 239, 172, 0); }
}
@media (max-width: 991.98px) {
    .rg-case { grid-template-columns: 1fr; min-height: 0; }
    .rg-case-board { padding: 24px 20px 8px; }
    .rg-case-board h1 { max-width: none; }
    .rg-case-ticket { margin: 0 12px 12px; }
}
@media (prefers-reduced-motion: reduce) {
    .rg-case-sweep, .rg-case-blip { animation: none; }
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
