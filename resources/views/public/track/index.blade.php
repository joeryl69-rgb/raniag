@extends('layouts.public')

@section('title', 'Track Report')

@section('content')
<div class="container">
    <section class="rg-case" aria-labelledby="rg-track-title">
        <div class="rg-case-board">
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
                   placeholder="RAN-AB12CD"
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
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
    border: 1px solid var(--rg-line);
}
.rg-case-board {
    position: relative;
    padding: 28px 28px 22px;
    color: var(--rg-text);
    background: #f4f7fb;
}
.rg-case-kicker {
    margin: 0 0 6px;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .16em;
    text-transform: uppercase;
    color: var(--rg-brand);
}
.rg-case-board h1 {
    margin: 0 0 8px;
    max-width: 16ch;
    font-size: clamp(1.7rem, 3vw, 2.2rem);
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--rg-text);
}
.rg-case-lead { margin: 0 0 18px; max-width: 36rem; color: var(--rg-muted); }
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
    background: #d5e2f2;
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
    background: #fff;
    border: 1px solid #d5e2f2;
    color: var(--rg-brand);
    position: relative;
    z-index: 1;
}
.rg-case-path li.is-live i {
    background: #0b5ed7;
    border-color: #93c5fd;
    color: #fff;
    box-shadow: 0 0 0 6px rgba(11, 94, 215, .25);
}
.rg-case-path strong { display: block; color: var(--rg-text); }
.rg-case-path span { color: var(--rg-muted); font-size: .86rem; }
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
@media (max-width: 991.98px) {
    .rg-case { grid-template-columns: 1fr; min-height: 0; }
    .rg-case-board { padding: 24px 20px 8px; }
    .rg-case-board h1 { max-width: none; }
    .rg-case-ticket { margin: 0 12px 12px; }
}
</style>
@endpush
