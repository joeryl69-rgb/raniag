@extends('layouts.public')

@section('title', 'Report Incident')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Report an Incident</h1>
        <p class="text-muted mb-0">Provide accurate details to help {{ config('raniag.organization') }} respond faster.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('public.report.store') }}" method="POST" enctype="multipart/form-data" id="incident-report-form" novalidate>
        @csrf

        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">1</span>
                <span>Incident Type</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($incidentTypes as $type)
                        <div class="col-sm-6 col-lg-4">
                            <label class="card raniag-type-card h-100 p-3 {{ (int) old('incident_type_id') === $type->id ? 'selected' : '' }}">
                                <input type="radio" name="incident_type_id" value="{{ $type->id }}"
                                       {{ (int) old('incident_type_id') === $type->id ? 'checked' : '' }} required>
                                <div class="d-flex align-items-start gap-2">
                                    <span class="badge rounded-pill" style="background: {{ $type->color ?? '#6c757d' }}">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold">{{ $type->name }}</div>
                                        @if ($type->description)
                                            <small class="text-muted">{{ $type->description }}</small>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('incident_type_id')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">2</span>
                <span>Incident Details</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label">Title <span class="text-muted">(optional)</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                               name="title" value="{{ old('title') }}" maxlength="255"
                               placeholder="Brief summary of the incident">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', 'medium') === $priority->value)>
                                    {{ $priority->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                                  name="description" rows="5" required minlength="10" maxlength="5000"
                                  placeholder="Describe what happened, when it occurred, and who may be affected...">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Minimum 10 characters.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">3</span>
                <span>Location</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <p class="text-muted small mb-0">Click the map, capture with GPS camera, or use your current location.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="use-current-location">
                        <i class="bi bi-crosshair me-1"></i>Use Current Location
                    </button>
                </div>
                <div id="incident-map" class="mb-3"></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="barangay" class="form-label">Barangay</label>
                        <input class="form-control @error('barangay') is-invalid @enderror" list="barangay-list"
                               id="barangay" name="barangay" value="{{ old('barangay') }}" placeholder="Select or type">
                        <datalist id="barangay-list">
                            @foreach ($barangays as $barangay)
                                <option value="{{ $barangay }}">
                            @endforeach
                        </datalist>
                        @error('barangay')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label for="location_address" class="form-label">Street / Landmark</label>
                        <input type="text" class="form-control @error('location_address') is-invalid @enderror"
                               id="location_address" name="location_address" value="{{ old('location_address') }}"
                               placeholder="e.g. Near municipal hall, main highway">
                        @error('location_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude"
                               name="latitude" value="{{ old('latitude') }}" readonly>
                        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude"
                               name="longitude" value="{{ old('longitude') }}" readonly>
                        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">4</span>
                <span>Reporter Information</span>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_anonymous" name="is_anonymous"
                           value="1" @checked(old('is_anonymous', true))>
                    <label class="form-check-label" for="is_anonymous">Report anonymously</label>
                </div>
                <div class="row g-3 reporter-fields" id="reporter-fields">
                    <div class="col-md-4">
                        <label for="reporter_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('reporter_name') is-invalid @enderror"
                               id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}">
                        @error('reporter_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="reporter_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control @error('reporter_phone') is-invalid @enderror"
                               id="reporter_phone" name="reporter_phone" value="{{ old('reporter_phone') }}">
                        @error('reporter_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="reporter_email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('reporter_email') is-invalid @enderror"
                               id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}">
                        @error('reporter_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card raniag-card mb-4">
            <div class="card-header raniag-card-header d-flex align-items-center gap-2 py-3">
                <span class="raniag-step-badge">5</span>
                <span>Evidence <span class="text-muted fw-normal">(optional)</span></span>
            </div>
            <div class="card-body">
                <input type="hidden" name="meta[gps_captures]" id="gps-capture-log" value="">

                <div id="gps-camera-module" class="mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div>
                            <h3 class="h6 fw-bold mb-1"><i class="bi bi-camera-video me-2"></i>GPS Camera</h3>
                            <p class="text-muted small mb-0">Capture geotagged photos using your device camera and GPS.</p>
                        </div>
                        <span class="badge bg-secondary" id="gps-camera-status">Camera off</span>
                    </div>

                    <div id="gps-camera-error" class="alert alert-warning d-none small" role="alert"></div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-primary" id="gps-camera-start">
                            <i class="bi bi-camera me-1"></i>Start Camera
                        </button>
                        <button type="button" class="btn btn-outline-danger d-none" id="gps-camera-stop">
                            <i class="bi bi-stop-circle me-1"></i>Stop
                        </button>
                        <button type="button" class="btn btn-success d-none" id="gps-camera-capture">
                            <i class="bi bi-camera-fill me-1"></i>Capture Photo
                        </button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="gps-camera-switch" title="Switch camera">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>

                    <div id="gps-camera-panel" class="d-none">
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <div class="gps-camera-viewport position-relative">
                                    <video id="gps-camera-video" class="w-100 rounded" playsinline autoplay muted></video>
                                    <canvas id="gps-camera-canvas" class="d-none"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card bg-light border-0 h-100">
                                    <div class="card-body">
                                        <h4 class="h6 fw-bold">Live GPS</h4>
                                        <p class="mb-1 font-monospace small" id="gps-camera-coords">—</p>
                                        <p class="text-muted small mb-0" id="gps-camera-accuracy">Waiting for signal…</p>
                                        <hr>
                                        <p class="small text-muted mb-0">
                                            Each capture tags the photo with coordinates and updates the map pin.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mt-2" id="gps-camera-preview"></div>
                </div>

                <hr class="my-4">

                <label for="evidence" class="form-label fw-semibold">Upload files</label>
                <input type="file" class="form-control @error('evidence') is-invalid @enderror @error('evidence.*') is-invalid @enderror"
                       id="evidence" name="evidence[]" multiple
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.mp4,.mov,.webm">
                <div class="form-text">
                    Up to {{ $evidenceConfig['max_files'] }} files total (camera + uploads),
                    {{ number_format($evidenceConfig['max_size_kb'] / 1024, 1) }} MB each.
                </div>
                @error('evidence')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('evidence.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <a href="{{ route('public.home') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg px-4" id="submit-report">
                <i class="bi bi-send me-2"></i>Submit Report
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    window.RANIAG_MAP = @json($mapConfig);
    window.RANIAG_GPS = @json($gpsConfig);
</script>
<script src="{{ asset('js/public-report.js') }}"></script>
<script src="{{ asset('js/gps-camera.js') }}"></script>
@endpush

