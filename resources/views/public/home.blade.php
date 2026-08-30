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
                <p class="text-muted small mb-0">Photos from the built-in camera carry your exact coordinates and barangay, so MDRRMO Pamplona finds the scene fast.</p>
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

    {{-- ===================== FEEDBACK / CONCERNS ===================== --}}
    <div class="row justify-content-center mt-5" id="feedback">
        <div class="col-lg-8">
            <div class="card raniag-card border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <div class="text-primary fs-2 mb-2"><i class="bi bi-chat-square-text"></i></div>
                        <h2 class="h5 fw-bold mb-1">Feedback &amp; Concerns</h2>
                        <p class="text-muted small mb-0">Encountered an issue, have a suggestion, or want to raise a concern about RANIAG or MDRRMO Pamplona's response? Let us know — this goes directly to our team.</p>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('public.feedback.store') }}">
                        @csrf
                        {{-- Honeypot field — hidden from real users via CSS, bots that
                             auto-fill every input will trip validation. --}}
                        <div style="position:absolute; left:-9999px;" aria-hidden="true">
                            <label for="website">Leave this field blank</label>
                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Type</label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                    <option value="feedback" selected>General Feedback</option>
                                    <option value="concern">Concern</option>
                                    <option value="suggestion">Suggestion</option>
                                    <option value="bug">Report a Bug/Issue</option>
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Subject</label>
                                <input type="text" name="subject" value="{{ old('subject') }}" maxlength="150" class="form-control @error('subject') is-invalid @enderror" required placeholder="Brief summary">
                                @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Message</label>
                                <textarea name="message" rows="4" maxlength="2000" class="form-control @error('message') is-invalid @enderror" required placeholder="Tell us more...">{{ old('message') }}</textarea>
                                @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Your Name <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="submitter_name" value="{{ old('submitter_name') }}" maxlength="150" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email <span class="text-muted fw-normal">(optional, for a reply)</span></label>
                                <input type="email" name="submitter_email" value="{{ old('submitter_email') }}" maxlength="150" class="form-control @error('submitter_email') is-invalid @enderror">
                                @error('submitter_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 text-center mt-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-send me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

