<x-app-layout>
    <x-slot name="header">
        {{ __('Incident Report Processing') }}
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            #show-incident-map {
                height: 300px;
                border-radius: 0.5rem;
                border: 1px solid #dee2e6;
                z-index: 1;
            }
            .evidence-img-container {
                position: relative;
                overflow: hidden;
                border-radius: 0.5rem;
                cursor: pointer;
            }
            .evidence-img-container img {
                aspect-ratio: 4 / 3;
                object-fit: cover;
                transition: transform 0.15s ease;
            }
            .evidence-img-container:hover img {
                transform: scale(1.05);
            }
            .gps-badge {
                position: absolute;
                top: 0.5rem;
                left: 0.5rem;
                z-index: 2;
                box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            }
        </style>
    @endpush

    <div class="d-flex mb-4">
        <a href="{{ route('admin.incidents.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>

    <div class="row g-4">
        <!-- Details Column -->
        <div class="col-lg-8">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Incident Details</h5>
                        <div>
                            <span class="font-monospace text-muted small">Tracking #: {{ $incident->tracking_number }}</span>
                            <div class="text-muted small mt-1">Location: @if($incident->location_address) {{ $incident->location_address }} @elseif($incident->barangay) Barangay {{ $incident->barangay }}, Pamplona, Cagayan @elseif($incident->latitude && $incident->longitude) Coordinates: {{ $incident->latitude }}, {{ $incident->longitude }} @else N/A @endif</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($incident->title)
                        <h3 class="h5 fw-bold text-dark mb-2">{{ $incident->title }}</h3>
                    @endif
                    <p class="lead fs-6 mb-4" style="white-space: pre-wrap;">{{ $incident->description }}</p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">Incident Category</div>
                                <span class="badge rounded-pill mt-1 text-white" style="background-color: {{ $incident->incidentType->color ?? '#6c757d' }}">
                                    {{ $incident->incidentType->name }}
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">Current Status</div>
                                <div class="mt-1"><x-public.status-badge :status="$incident->status" /></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">Reported Priority</div>
                                <span class="badge bg-secondary text-capitalize mt-1">{{ $incident->priority->label() ?? $incident->priority }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">Reported At</div>
                                <strong class="text-dark d-block mt-1">{{ $incident->reported_at->format('M d, Y h:i A') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evidence Section -->
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-images me-2 text-primary"></i>Attachments / Evidence</h5>
                </div>
                <div class="card-body">
                    @php
                        $publicEvidence = $incident->evidence->whereNull('uploaded_by');
                    @endphp
                    @if ($publicEvidence->isEmpty())
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-file-earmark-x display-5"></i>
                            <p class="mt-2 mb-0">No photos or files uploaded for this incident.</p>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach ($publicEvidence->sortByDesc('priority') as $ev)
                                <div class="col-sm-6 col-md-4">
                                    <div class="card h-100 border shadow-sm">
                                        <div class="evidence-img-container">
                                            @if ($ev->is_gps_capture)
                                                <span class="badge bg-success gps-badge"><i class="bi bi-geo-alt-fill me-1"></i>GPS Cam</span>
                                            @endif
                                            
                                            @if (str_starts_with($ev->mime_type, 'image/'))
                                                <a href="{{ Storage::url($ev->file_path) }}" target="_blank">
                                                    <img src="{{ Storage::url($ev->file_path) }}" class="card-img-top" alt="Evidence">
                                                </a>
                                            @else
                                                <div class="d-flex align-items-center justify-content-center bg-light text-secondary card-img-top" style="height: 150px;">
                                                    <i class="bi bi-file-earmark fs-1"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-body p-2 small">
                                            <div class="text-truncate" title="{{ $ev->original_filename }}">{{ $ev->original_filename }}</div>
                                            <div class="text-muted">{{ number_format($ev->file_size / 1024, 1) }} KB</div>
                                            <a href="{{ Storage::url($ev->file_path) }}" download class="btn btn-link btn-sm p-0 mt-1"><i class="bi bi-download me-1"></i>Download</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Timeline Section -->
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Activity & Status Timeline</h5>
                </div>
                <div class="card-body">
                    @if ($incident->statusTimeline->isEmpty())
                        <p class="text-muted mb-0">No history available.</p>
                    @else
                        <div class="raniag-timeline">
                            @foreach ($incident->statusTimeline as $update)
                                <div class="raniag-timeline-item">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                                        <div>
                                            <x-public.status-badge :status="$update->to_status" />
                                            <span class="text-muted small ms-2">by {{ $update->user?->display_title ?? 'System/Public' }}</span>
                                        </div>
                                        <small class="text-muted">{{ $update->created_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                    @if ($update->comment)
                                        <p class="mb-0 text-dark p-2 bg-light rounded-3 mt-1 small">{{ $update->comment }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar / Actions Column -->
        <div class="col-lg-4">
            <!-- Location Map -->
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt me-2 text-primary"></i>Incident Location</h5>
                </div>
                <div class="card-body">
                    @if ($incident->barangay || $incident->location_address)
                        <div class="mb-3">
                            <strong>{{ $incident->barangay }}</strong>
                            @if ($incident->location_address)
                                <div class="text-muted small">{{ $incident->location_address }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($incident->latitude && $incident->longitude)
                        <div id="show-incident-map" class="mb-2"></div>
                        <div class="text-muted font-monospace small text-center mt-1">
                            Coordinates: {{ $incident->latitude }}, {{ $incident->longitude }}
                        </div>
                    @else
                        <div class="alert alert-warning mb-0 text-center py-3">
                            <i class="bi bi-geo fs-4"></i>
                            <p class="mb-0 mt-1 small">No coordinates pinned for this incident.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Reporter Profile -->
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Reporter Details</h5>
                </div>
                <div class="card-body">
                    @if ($incident->is_anonymous)
                        <div class="d-flex align-items-center gap-3">
                            <span class="fs-1 text-secondary"><i class="bi bi-person-fill-lock"></i></span>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark">Anonymous Reporter</h6>
                                <p class="mb-0 text-muted small">No identity details provided</p>
                            </div>
                        </div>
                    @else
                        <dl class="mb-0">
                            <dt class="text-muted small">Name</dt>
                            <dd class="mb-2 text-dark fw-semibold">{{ $incident->reporter_name ?? 'N/A' }}</dd>
                            
                            <dt class="text-muted small">Phone Number</dt>
                            <dd class="mb-2 text-dark">{{ $incident->reporter_phone ?? 'N/A' }}</dd>
                            
                            <dt class="text-muted small">Email Address</dt>
                            <dd class="mb-0 text-dark">{{ $incident->reporter_email ?? 'N/A' }}</dd>
                        </dl>
                    @endif
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="card raniag-card shadow-sm border-primary border-0 mb-4">
                <div class="card-header bg-primary text-white py-3 rounded-top">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2"></i>Action Dispatcher</h5>
                </div>
                <div class="card-body">
                    @if ($incident->status->value === 'submitted')
                        <!-- Stage 2: Validate Report -->
                        <h6 class="fw-bold mb-3">Stage 2: Validation Check</h6>
                        <form action="{{ route('admin.incidents.validate', $incident->id) }}" method="POST" id="validation-form">
                            @csrf
                            <div class="mb-3">
                                <label for="validation_action" class="form-label">Review Verdict</label>
                                <select class="form-select" name="action" id="validation_action" required onchange="toggleValidationView()">
                                    <option value="">Choose action...</option>
                                    <option value="approve">Approve & Assign Agency</option>
                                    <option value="reject">Reject Report</option>
                                </select>
                            </div>

                            <div class="mb-3 d-none" id="agency-select-container">
                                <label class="form-label">Select Government Branch(es) or Personnel</label>
                                <div class="border rounded-3 p-3 bg-white" style="max-height: 220px; overflow-y: auto;">
                                    @foreach ($agencies as $agency)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="assigned_agency_id[]" value="{{ $agency->id }}" id="agency_check_2_{{ $agency->id }}">
                                            <label class="form-check-label" for="agency_check_2_{{ $agency->id }}">
                                                {{ $agency->code }} ({{ $agency->name }})
                                            </label>
                                        </div>
                                    @endforeach
                                    @if ($personnel->isNotEmpty())
                                        <hr class="my-3">
                                        <div class="fw-semibold mb-2">Internal Personnel</div>
                                        @foreach ($personnel as $person)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="assigned_personnel_id[]" value="{{ $person->id }}" id="personnel_check_2_{{ $person->id }}">
                                                <label class="form-check-label" for="personnel_check_2_{{ $person->id }}">
                                                    {{ $person->name }} @if($person->role_title) ({{ $person->role_title }}) @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>


                            <div class="mb-3">
                                <label for="notes" class="form-label">Validation Comments</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Explain the decision or special directions..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-shield-fill-check me-1"></i>Process Verdict
                            </button>
                        </form>
                    @elseif ($incident->status->value === 'received')
                        <!-- Stage 3: Assign Agency -->
                        <h6 class="fw-bold mb-3">Stage 3: Dispatch Assignment</h6>
                        <form action="{{ route('admin.incidents.validate', $incident->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="approve">
                            
                            <div class="mb-3">
                                <label class="form-label">Select Government Branch(es) or Personnel</label>
                                <div class="border rounded-3 p-3 bg-white" style="max-height: 220px; overflow-y: auto;">
                                    @foreach ($agencies as $agency)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="assigned_agency_id[]" value="{{ $agency->id }}" id="agency_check_3_{{ $agency->id }}">
                                            <label class="form-check-label" for="agency_check_3_{{ $agency->id }}">
                                                {{ $agency->code }} ({{ $agency->name }})
                                            </label>
                                        </div>
                                    @endforeach
                                    @if ($personnel->isNotEmpty())
                                        <hr class="my-3">
                                        <div class="fw-semibold mb-2">Internal Personnel</div>
                                        @foreach ($personnel as $person)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="assigned_personnel_id[]" value="{{ $person->id }}" id="personnel_check_3_{{ $person->id }}">
                                                <label class="form-check-label" for="personnel_check_3_{{ $person->id }}">
                                                    {{ $person->name }} @if($person->role_title) ({{ $person->role_title }}) @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="form-text">Check all agencies or personnel you want to dispatch.</div>
                            </div>


                            <div class="mb-3">
                                <label for="notes" class="form-label">Dispatch Notes</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Provide emergency response details..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send-fill me-1"></i>Dispatch Agency
                            </button>
                        </form>
                    @elseif ($incident->assignments->isNotEmpty() || $incident->agency)
                        <!-- Assigned status or beyond -->
                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Assigned Response Agencies / Personnel</div>
                            @if ($incident->assignments->isNotEmpty())
                                @foreach ($incident->assignments as $assignment)
                                    @php
                                        $assignmentResolution = null;
                                        if ($assignment->agency) {
                                            $assignmentResolution = $incident->resolutions->where('resolver.agency_id', $assignment->agency_id)->first();
                                        } elseif ($assignment->assignee) {
                                            $assignmentResolution = $incident->resolutions->where('resolver.id', $assignment->assignee->id)->first();
                                        }
                                    @endphp
                                    <div class="d-flex justify-content-between align-items-center mt-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <h6 class="fw-bold text-dark mb-0">
                                            {{ $assignment->agency ? $assignment->agency->name . ' (' . $assignment->agency->code . ')' : $assignment->assignee?->display_title ?? 'Personnel' }}
                                        </h6>
                                        @if ($assignment->notes)
                                            <div class="small text-muted mt-1">Dispatch notes: {{ $assignment->notes }}</div>
                                        @endif
                                        @if ($assignment->is_active)
                                            <span class="badge bg-warning text-dark">Active</span>
                                        @else
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-success">Completed</span>
                                                @if($assignmentResolution)
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0" data-bs-toggle="modal" data-bs-target="#editAdminResModal{{ $assignmentResolution->id }}" title="Edit Resolution Report">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @if (!$assignment->is_active && $assignmentResolution)
                                        <div class="mt-2 mb-3 bg-white border p-2 rounded small text-dark" style="white-space: pre-wrap;">{{ $assignmentResolution->summary }}</div>

                                        @php
                                            $assignmentEvidence = $incident->evidence->filter(function($ev) use ($assignment) {
                                                if (! $ev->uploader) {
                                                    return false;
                                                }

                                                if ($assignment->agency) {
                                                    return $ev->uploader->agency_id === $assignment->agency_id;
                                                }

                                                return $ev->uploader->id === $assignment->assigned_to;
                                            });
                                        @endphp
                                        @if($assignmentEvidence->isNotEmpty())
                                            <div class="row g-2 mb-3">
                                                @foreach($assignmentEvidence as $ev)
                                                    <div class="col-4 col-sm-3">
                                                        <a href="{{ Storage::url($ev->file_path) }}" target="_blank">
                                                            @if(str_starts_with($ev->mime_type, 'image/'))
                                                                <img src="{{ Storage::url($ev->file_path) }}" class="img-fluid rounded border shadow-sm" style="aspect-ratio: 4/3; object-fit: cover;" alt="Evidence">
                                                            @else
                                                                <div class="d-flex align-items-center justify-content-center bg-light text-secondary rounded border shadow-sm" style="aspect-ratio: 4/3;">
                                                                    <i class="bi bi-file-earmark fs-4"></i>
                                                                </div>
                                                            @endif
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="modal fade" id="editAdminResModal{{ $assignmentResolution->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('admin.incidents.resolutions.update', [$incident->id, $assignmentResolution->id]) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Report: {{ $assignment->agency ? $assignment->agency->name : $assignment->assignee?->display_title ?? 'Personnel' }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Resolution Summary <span class="text-danger">*</span></label>
                                                                <textarea class="form-control" name="summary" rows="4" required minlength="20">{{ old('summary', $assignmentResolution->summary) }}</textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Actions Taken <span class="text-danger">*</span></label>
                                                                <textarea class="form-control" name="actions_taken" rows="4" required minlength="20">{{ old('actions_taken', $assignmentResolution->actions_taken) }}</textarea>
                                                            </div>
                                                            <div class="alert alert-warning small mb-0">
                                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                                You are modifying the official resolution report submitted for this assignment.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Override</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <h6 class="fw-bold text-dark mt-1 mb-0">{{ $incident->agency->name }} ({{ $incident->agency->code }})</h6>
                            @endif
                            @if ($incident->status->value === 'assigned')
                                <span class="badge bg-warning text-dark mt-2">Awaiting Agency Response</span>
                            @elseif ($incident->status->value === 'in_progress')
                                <span class="badge bg-info mt-2">Under Investigation</span>
                            @elseif ($incident->status->value === 'resolved')
                                <span class="badge bg-success mt-2">Resolved</span>
                                <div class="mt-3">
                                    <span class="text-muted small">This incident is already resolved. No further admin close action is required.</span>
                                </div>
                            @elseif ($incident->status->value === 'closed')

                                <span class="badge bg-dark mt-2">Case Closed & Archived</span>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-info mb-0 small">
                            This report was processed (verdict: {{ $incident->status->value }}).
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @if ($incident->latitude && $incident->longitude)
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const lat = {{ $incident->latitude }};
                    const lng = {{ $incident->longitude }};
                    const map = L.map('show-incident-map').setView([lat, lng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);
                    L.marker([lat, lng]).addTo(map)
                        .bindPopup('Incident Location')
                        .openPopup();
                });
            </script>
        @endif
        <script>
            function toggleValidationView() {
                const action = document.getElementById('validation_action').value;
                const container = document.getElementById('agency-select-container');

                if (action === 'approve') {
                    container.classList.remove('d-none');
                } else {
                    container.classList.add('d-none');

                    // Clear checkboxes when switching to reject.
                    container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                }
            }
        </script>

    @endpush
</x-app-layout>
