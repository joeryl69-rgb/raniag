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
</div>
@endsection

