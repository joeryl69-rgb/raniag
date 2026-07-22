<x-app-layout>
    <x-slot name="header">
        {{ __('Incident Case File') }}
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            #agency-incident-map {
                height: 250px;
                border-radius: 0.5rem;
                border: 1px solid #dee2e6;
                z-index: 1;
            }
            .evidence-thumb {
                aspect-ratio: 4 / 3;
                object-fit: cover;
            }
        </style>
    @endpush

    <div class="d-flex mb-4">
        <a href="{{ route('agency.incidents.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dispatches
        </a>
    </div>

    <div class="row g-4">
        <!-- Details Column -->
        <div class="col-lg-8">
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-folder-fill text-primary me-2"></i>Case Details</h5>
                        <span class="font-monospace text-muted small">Tracking #: {{ $incident->tracking_number }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($incident->title)
                        <h3 class="h5 fw-bold text-dark mb-2">{{ $incident->title }}</h3>
                    @endif
                    <p class="mb-4 text-dark fs-6" style="white-space: pre-wrap;">{{ $incident->description }}</p>

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
                                <div class="text-muted small">Assigned Priority</div>
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

            <!-- Evidence / Media -->
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-images text-primary me-2"></i>Attached Evidence</h5>
                </div>
                <div class="card-body">
                    @if ($incident->evidence->isEmpty())
                        <p class="text-muted mb-0">No evidence attached.</p>
                    @else
                        <div class="row g-2">
                            @foreach ($incident->evidence as $ev)
                                <div class="col-6 col-md-4">
                                    <div class="card h-100 border">
                                        @if (str_starts_with($ev->mime_type, 'image/'))
                                            <a href="{{ Storage::url($ev->file_path) }}" target="_blank">
                                                <img src="{{ Storage::url($ev->file_path) }}" class="card-img-top evidence-thumb" alt="Evidence">
                                            </a>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center bg-light text-secondary card-img-top evidence-thumb">
                                                <i class="bi bi-file-earmark fs-1"></i>
                                            </div>
                                        @endif
                                        <div class="card-body p-2 small">
                                            <div class="text-truncate">{{ $ev->original_filename }}</div>
                                            <a href="{{ Storage::url($ev->file_path) }}" download class="btn btn-link btn-sm p-0 mt-1"><i class="bi bi-download me-1"></i>Download</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- History Timeline -->
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Investigation updates Log</h5>
                </div>
                <div class="card-body">
                    @if ($incident->statusUpdates->isEmpty())
                        <p class="text-muted mb-0">No history logged.</p>
                    @else
                        <div class="raniag-timeline">
                            @foreach ($incident->statusUpdates as $update)
                                <div class="raniag-timeline-item">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                                        <div>
                                            <x-public.status-badge :status="$update->to_status" />
                                            <span class="text-muted small ms-2">by {{ $update->user?->display_title ?? 'System/Public' }}</span>
                                        </div>
                                        <small class="text-muted">{{ $update->created_at->format('M d, Y h:i A') }}</small>
                                    </div>
                                    @if ($update->comment)
                                        <p class="mb-0 text-dark p-2 bg-light rounded mt-1 small">{{ $update->comment }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Actions Column -->
        <div class="col-lg-4">
            <!-- Map Location -->
            <div class="card raniag-card shadow-sm border-0 mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-geo-alt text-primary me-2"></i>Map Coordinates</h5>
                </div>
                <div class="card-body">
                    @if ($incident->barangay || $incident->location_address)
                        <div class="mb-2">
                            <strong>Barangay:</strong> {{ $incident->barangay }}
                            @if ($incident->location_address)
                                <div class="text-muted small">{{ $incident->location_address }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($incident->latitude && $incident->longitude)
                        <div id="agency-incident-map" class="mb-2"></div>
                    @else
                        <div class="alert alert-light text-center mb-0 text-muted">No location coordinates set.</div>
                    @endif
                </div>
            </div>

            <!-- Case Action Terminal -->
            <div class="card raniag-card shadow-sm border-primary border-0">
                <div class="card-header bg-primary text-white py-3 rounded-top">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-play-circle me-2"></i>Case Action Control</h5>
                </div>
                <div class="card-body">
                    @if ($incident->status->value === 'assigned')
                        <!-- Action: Accept assignment -->
                        <div class="p-3 text-center">
                            <p class="text-muted small mb-3">Accept this dispatch to indicate your branch has received the alert and is initiating investigation.</p>
                            <form action="{{ route('agency.incidents.accept', $incident->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check2-circle me-1"></i>Accept & Acknowledge</button>
                            </form>
                        </div>
                    @elseif ($incident->status->value === 'in_progress' || $incident->status->value === 'pending_info')
                        @php
                            $agencyId = auth()->user()->agency_id;
                            $hasActiveAssignment = \App\Models\Assignment::where('incident_id', $incident->id)
                                ->where('agency_id', $agencyId)
                                ->where('is_active', true)
                                ->exists();
                        @endphp

                        @if ($hasActiveAssignment)
                            <!-- Actions: Update progress or Resolve -->
                            
                            <!-- Update Status Form -->
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Log Investigation Update</h6>
                            <form action="{{ route('agency.incidents.update_status', $incident->id) }}" method="POST" class="mb-4">
                                @csrf
                                @method('PATCH')
                                <div class="mb-3">
                                    <label for="status" class="form-label">Investigation Phase</label>
                                    <select class="form-select" name="status" id="status" required onchange="toggleInfoView()">
                                        <option value="in_progress" @selected($incident->status->value === 'in_progress')>In Progress / Active Investigation</option>
                                        <option value="pending_info" @selected($incident->status->value === 'pending_info')>Awaiting Info / Pending Request</option>
                                    </select>
                                </div>

                                <div class="mb-3 d-none" id="info-request-container">
                                    <label for="needs_info" class="form-label">Requested Details</label>
                                    <input type="text" class="form-control" name="needs_info" id="needs_info" placeholder="What details do you need from MDRRMO Pamplona?">
                                </div>

                                <div class="mb-3">
                                    <label for="comment" class="form-label">Investigation Logs</label>
                                    <textarea class="form-control" name="comment" id="comment" rows="3" required placeholder="Type comments visible to public/admin..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-file-earmark-diff me-1"></i>Post Update</button>
                            </form>

                            <!-- Submit Resolution Form -->
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Complete & Close Incident</h6>
                            <form action="{{ route('agency.incidents.resolution', $incident->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="summary" class="form-label">Resolution Summary <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="summary" id="summary" rows="3" required minlength="20" placeholder="Summarize the final resolution findings (min 20 chars)..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="actions_taken" class="form-label">Actions Taken <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="actions_taken" id="actions_taken" rows="3" required minlength="20" placeholder="Detail the physical or technical actions taken (min 20 chars)..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="evidence" class="form-label">Resolution Photos / Reports <span class="text-muted">(optional)</span></label>
                                    <input class="form-control" type="file" name="evidence[]" id="evidence" multiple accept=".jpg,.jpeg,.png,.pdf">
                                </div>

                                <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-all me-1"></i>Resolve Incident</button>
                            </form>
                        @else
                            @php
                                $agencyResolution = null;
                                foreach($incident->resolutions as $res) {
                                    if ($res->resolver && $res->resolver->agency_id === $agencyId) {
                                        $agencyResolution = $res;
                                        break;
                                    }
                                }
                            @endphp
                            
                            @if($agencyResolution)
                                <div class="p-3 bg-light rounded-3">
                                    <div class="text-muted small">Your Resolution Findings</div>
                                    <p class="mb-0 text-dark fw-semibold mt-1 small" style="white-space: pre-wrap;">{{ $agencyResolution->summary ?? 'Case resolved successfully.' }}</p>
                                    <div class="d-flex align-items-center justify-content-between mt-2">
                                        <span class="badge bg-success">Awaiting Other Agencies</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editResolutionModal">
                                            <i class="bi bi-pencil-square me-1"></i>Edit My Resolution
                                        </button>
                                    </div>
                                </div>

                                <!-- Edit Resolution Modal -->
                                <div class="modal fade" id="editResolutionModal" tabindex="-1" aria-labelledby="editResolutionModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('agency.incidents.resolution.update', [$incident->id, $agencyResolution->id]) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editResolutionModalLabel">Edit Resolution Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="edit_summary" class="form-label">Resolution Summary <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="summary" id="edit_summary" rows="3" required minlength="20">{{ old('summary', $agencyResolution->summary) }}</textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="edit_actions_taken" class="form-label">Actions Taken <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" name="actions_taken" id="edit_actions_taken" rows="3" required minlength="20">{{ old('actions_taken', $agencyResolution->actions_taken) }}</textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="edit_evidence" class="form-label">Attach More Evidence <span class="text-muted">(optional)</span></label>
                                                        <input class="form-control" type="file" name="evidence[]" id="edit_evidence" multiple accept=".jpg,.jpeg,.png,.pdf">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @elseif ($incident->status->value === 'resolved' || $incident->status->value === 'closed')
                        <!-- Resolution summary display -->
                        @php
                            $resolution = $incident->resolutions->last() ?? $incident->resolution;
                        @endphp

                        @php
                            // For the printable request feature: only show request buttons when the incident is resolved/closed.
                            // Scope the lookup to the current agency so agencies only see their own requests.
                            $agencyId = auth()->user()->agency_id;
                            $documentRequest = $incident->documentRequests()
                                ->where('requesting_agency_id', $agencyId)
                                ->latest('created_at')
                                ->first();
                        @endphp
 
                        @if($documentRequest && in_array($documentRequest->status, ['approved', 'sent']) && $documentRequest->generated_path)
                            <a href="{{ Storage::url($documentRequest->generated_path) }}" target="_blank" class="btn btn-success w-100 mb-3">
                                <i class="bi bi-file-earmark-pdf me-2"></i>View Printable PDF
                            </a>
                            @if($documentRequest->status === 'sent')
                                <a href="https://mail.google.com/mail/u/0/#inbox" target="_blank" class="btn btn-outline-primary w-100 mb-3">
                                    <i class="bi bi-envelope me-2"></i>Check Gmail for Approved Report
                                </a>
                            @endif
                        @else
                            <form method="POST" action="{{ route('agency.incidents.print_requests.store', $incident->id) }}" class="mb-3">
                                @csrf
                                <input type="hidden" name="request_type" value="single">
                                <div class="mb-3">
                                    <label for="request_note" class="form-label">Request Details</label>
                                    <textarea class="form-control" id="request_note" name="request_note" rows="3" placeholder="Describe what you need in the printable copy (optional)"></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-primary w-100" @disabled(($documentRequest && in_array($documentRequest->status, ['pending','approved','sent'])))>
                                    <i class="bi bi-file-earmark-text me-2"></i>
                                    @if($documentRequest && $documentRequest->status === 'rejected')
                                        Re-request Printable Copy
                                    @elseif($documentRequest && in_array($documentRequest->status, ['pending','approved','sent']))
                                        Printable Request Pending/Processed
                                    @else
                                        Request Printable Copy
                                    @endif
                                </button>
                            </form>
                        @endif

                        @php
                            $agencyId = auth()->user()->agency_id;
                            $agencyResolution = null;
                            foreach($incident->resolutions as $res) {
                                if ($res->resolver && $res->resolver->agency_id === $agencyId) {
                                    $agencyResolution = $res;
                                    break;
                                }
                            }
                        @endphp

                        <div class="p-3 bg-light rounded-3">
                            <div class="text-muted small">Resolution Findings</div>
                            <p class="mb-0 text-dark fw-semibold mt-1 small" style="white-space: pre-wrap;">{{ $agencyResolution->summary ?? ($resolution->summary ?? 'Case resolved successfully.') }}</p>
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <span class="badge bg-success">Case Complete</span>
                                <!-- Editing is intentionally disabled since the global status is now resolved/closed -->
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light text-center py-2 mb-0 small text-muted">This case is inactive.</div>
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
                    const map = L.map('agency-incident-map').setView([lat, lng], 15);
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
            function toggleInfoView() {
                const status = document.getElementById('status').value;
                const container = document.getElementById('info-request-container');
                const infoInput = document.getElementById('needs_info');
                if (status === 'pending_info') {
                    container.classList.remove('d-none');
                    infoInput.required = true;
                } else {
                    container.classList.add('d-none');
                    infoInput.required = false;
                    infoInput.value = '';
                }
            }
        </script>
    @endpush
</x-app-layout>
