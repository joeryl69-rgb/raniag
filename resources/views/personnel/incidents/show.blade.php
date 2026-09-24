<x-app-layout>
    <x-slot name="header">
        {{ __('Incident Case File') }}
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            .case-map-frame {
                width: 100%;
                aspect-ratio: 16 / 10;
                min-height: 220px;
                max-height: min(55vh, 420px);
                position: relative;
                overflow: hidden;
                border-radius: 0.75rem;
                background: #eef5f0;
                border: 1px solid #dee2e6;
            }
            .case-map-frame #personnel-incident-map,
            .case-map-frame .leaflet-container {
                position: absolute;
                inset: 0;
                width: 100% !important;
                height: 100% !important;
                z-index: 1;
                border: 0;
            }
            @media (max-width: 767.98px) {
                .case-map-frame { aspect-ratio: 4 / 3; max-height: 50vh; }
            }
            .case-unit-strip {
                display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
                padding: .65rem .85rem; font-size: .82rem; color: #334155;
                background: #f8fafc; border-top: 1px solid #e2e8f0;
            }
            .case-unit-strip .live-dot {
                width: 8px; height: 8px; border-radius: 50%; background: #16a34a;
                box-shadow: 0 0 0 0 rgba(22,163,74,.55);
                animation: case-live-pulse 1.8s infinite;
            }
            @keyframes case-live-pulse {
                0% { box-shadow: 0 0 0 0 rgba(22,163,74,.55); }
                70% { box-shadow: 0 0 0 8px rgba(22,163,74,0); }
                100% { box-shadow: 0 0 0 0 rgba(22,163,74,0); }
            }
            .evidence-thumb {
                aspect-ratio: 4 / 3;
                object-fit: cover;
            }
        </style>
    @endpush

    <div class="d-flex mb-3 mb-md-4">
        <a href="{{ route('personnel.incidents.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Dispatches
        </a>
    </div>

    <div class="card raniag-card rg-app-card mb-3 overflow-hidden">
        <div class="card-header raniag-card-header bg-white py-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <h5 class="mb-0 fw-bold"><i class="bi bi-broadcast-pin text-primary me-2"></i>Live Response Map</h5>
            <span class="font-monospace text-muted small">#{{ $incident->tracking_number }}</span>
        </div>
        <div class="card-body p-0">
            @if ($incident->barangay || $incident->location_address)
                <div class="px-3 pt-3 pb-2 small">
                    <strong>{{ $incident->barangay ?: 'Location' }}</strong>
                    @if ($incident->location_address)
                        <span class="text-muted">· {{ $incident->location_address }}</span>
                    @endif
                </div>
            @endif
            @if ($incident->latitude && $incident->longitude)
                <div class="case-map-frame mx-3 mb-0">
                    <div id="personnel-incident-map"></div>
                </div>
                <div class="case-unit-strip mx-3 mb-3 rounded-bottom">
                    <span class="live-dot" aria-hidden="true"></span>
                    <span id="personnel-units-status">Checking responder GPS…</span>
                </div>
            @else
                <div class="alert alert-light text-center m-3 mb-3 text-muted">No location coordinates set for this incident.</div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- Details Column -->
        <div class="col-lg-7 order-2">
            <div class="card raniag-card rg-app-card mb-4">
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
                                <span class="badge rounded-pill mt-1 text-white" style="background-color: {{ $incident->incidentType?->color ?? '#6c757d' }}">
                                    {{ $incident->incidentType?->name ?? 'Incident' }}
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
                                <span class="badge bg-secondary text-capitalize mt-1">{{ $incident->priority?->label() ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">Reported At</div>
                                <strong class="text-dark d-block mt-1">{{ $incident->reported_at?->format('M d, Y h:i A') ?? '—' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evidence / Media -->
            <div class="card raniag-card rg-app-card mb-4">
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
                                            <a href="{{ $ev->url() }}" class="js-lightbox" data-group="evidence-{{ $incident->id }}" data-caption="{{ $ev->original_filename }}">
                                                <img src="{{ $ev->url() }}" class="card-img-top evidence-thumb" alt="Evidence">
                                            </a>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center bg-light text-secondary card-img-top evidence-thumb">
                                                <i class="bi bi-file-earmark fs-1"></i>
                                            </div>
                                        @endif
                                        <div class="card-body p-2 small">
                                            <div class="text-truncate">{{ $ev->original_filename }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                @if ($ev->is_gps_capture)
                                                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="bi bi-geo-alt-fill"></i> GPS Capture</span>
                                                @endif
                                                @if ($ev->uploader)
                                                    <div class="mt-1"><i class="bi bi-person-fill me-1"></i>{{ $ev->uploader->name }}</div>
                                                @else
                                                    <div class="mt-1"><i class="bi bi-person me-1"></i>Public reporter</div>
                                                @endif
                                            </div>
                                            <a href="{{ $ev->url() }}" download class="btn btn-link btn-sm p-0 mt-1"><i class="bi bi-download me-1"></i>Download</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- History Timeline -->
            <div class="card raniag-card rg-app-card mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Investigation updates Log</h5>
                </div>
                <div class="card-body">
                    @if ($incident->statusTimeline->isEmpty())
                        <p class="text-muted mb-0">No history logged.</p>
                    @else
                        <div class="raniag-timeline raniag-timeline-wide">
                            @foreach ($incident->statusTimeline as $update)
                                <div class="raniag-timeline-item" data-status="{{ $update->to_status->value ?? $update->to_status }}">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                                        <div>
                                            <x-public.status-badge :status="$update->to_status" :comment="$update->comment" />
                                            <span class="text-muted small ms-2">by {{ $update->user?->display_title ?? 'System/Public' }}{{ $update->user?->agency ? ' · '.$update->user->agency->name : '' }}</span>
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
        <div class="col-lg-5 order-1">
            <!-- Case Action Control -->
            <div class="card rg-console-card">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-broadcast me-2"></i>Case Action Control</h5>
                </div>
                <div class="card-body">
                    @php
                        $userId = auth()->id();
                        $agencyId = auth()->user()?->agency_id;
                        $myAssignment = \App\Models\Assignment::where('incident_id', $incident->id)
                            ->where('is_active', true)
                            ->where(function ($q) use ($userId, $agencyId) {
                                $q->where('assigned_to', $userId);
                                if ($agencyId) {
                                    $q->orWhere('agency_id', $agencyId);
                                }
                            })
                            ->latest('created_at')
                            ->first();
                        $needsAcceptance = $myAssignment && ! $myAssignment->isAcknowledged()
                            && ! in_array($incident->status->value, ['resolved', 'closed']);
                    @endphp
                    @if ($needsAcceptance)
                        <!-- Action: Accept assignment — confirm modal first, then submit -->
                        <div class="p-3 text-center">
                            <p class="text-muted small mb-3">Accept this dispatch to indicate your branch has received the alert and is initiating investigation. Location is not required to accept.</p>
                            <button type="button" class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#acceptAcknowledgeModal">
                                <i class="bi bi-check2-circle me-1"></i>Accept & Acknowledge
                            </button>
                        </div>
                        <x-confirm-action-modal
                            id="acceptAcknowledgeModal"
                            title="Confirm Accept & Acknowledge"
                            confirm-label="Confirm & Accept"
                            confirm-class="btn-primary"
                            :form-action="route('personnel.incidents.accept', $incident->id)"
                            :incident="$incident"
                        />
                    @elseif ($incident->status->value === 'in_progress' || $incident->status->value === 'pending_info')
                        @php
                            $hasActiveAssignment = $myAssignment !== null;
                        @endphp

                        @if ($hasActiveAssignment)
                            <x-responder-field-strip
                                :incident="$incident"
                                :assignment="$myAssignment"
                                :phase-route="route('personnel.incidents.field_phase', $incident)"
                                :sms-route="route('personnel.incidents.sms_reporter', $incident)"
                            />

                            @php $smsThread = $incident->smsLogs()->latest()->limit(20)->get(); @endphp
                            @if ($smsThread->isNotEmpty())
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-2">SMS thread</h6>
                                    <div class="small border rounded p-2" style="max-height:180px;overflow:auto;">
                                        @foreach ($smsThread as $sms)
                                            <div class="mb-2">
                                                <div class="text-muted">{{ $sms->created_at?->format('M j, g:ia') }} · {{ $sms->status?->value ?? $sms->status }}</div>
                                                <div>{{ $sms->message }}</div>
                                                @if ($sms->thread_note)
                                                    <div class="fst-italic text-muted">Note: {{ $sms->thread_note }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Actions: Update progress or Resolve -->
                            
                            <!-- Update Status Form -->
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Log Investigation Update</h6>
                            <form action="{{ route('personnel.incidents.update_status', $incident->id) }}" method="POST" class="mb-4">
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
                            <form action="{{ route('personnel.incidents.resolution', $incident->id) }}" method="POST" enctype="multipart/form-data" id="resolutionForm">
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
                                    <x-gps-camera />
                                    <label for="evidence" class="form-label">Resolution Photos / Reports <span class="text-muted">(optional)</span></label>
                                    <input class="form-control" type="file" name="evidence[]" id="evidence" multiple accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="form-text">Use the GPS Camera above for a geotagged, watermarked photo, or attach files directly here.</div>
                                </div>

                                <button type="button" class="btn btn-success" id="resolutionReviewBtn"><i class="bi bi-check-all me-1"></i>Resolve Incident</button>
                            </form>

                            <div class="modal fade" id="resolutionReviewModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><i class="bi bi-clipboard-check me-2 text-success"></i>Confirm Resolution Submission</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small">Review what you're about to submit — this can't be edited after other agencies have also resolved.</p>
                                            <div class="mb-3">
                                                <div class="text-muted small fw-semibold">Resolution Summary</div>
                                                <p class="mb-0" id="reviewSummary" style="white-space: pre-wrap;"></p>
                                            </div>
                                            <div class="mb-3">
                                                <div class="text-muted small fw-semibold">Actions Taken</div>
                                                <p class="mb-0" id="reviewActionsTaken" style="white-space: pre-wrap;"></p>
                                            </div>
                                            <div class="mb-0">
                                                <div class="text-muted small fw-semibold">Evidence Attached</div>
                                                <p class="mb-0" id="reviewEvidenceCount"></p>
                                            </div>
                                            <div class="alert alert-light border small mt-3 mb-0">
                                                <i class="bi bi-info-circle me-1"></i>This records <strong>your</strong> resolution. If other agencies/personnel are also assigned to this incident, they submit theirs independently — the incident closes once everyone assigned has resolved.
                                            </div>
                                            {{-- The green "Confirm & Submit" button used to be enabled the moment
                                                 the modal opened, so it read as active/actionable before the person
                                                 had actually looked at what they were about to send. It now starts
                                                 neutral (btn-outline-secondary) and disabled, and only switches to
                                                 green once this box is checked — green means "reviewed and ready",
                                                 not just "modal is open". --}}
                                            <div class="form-check mt-3">
                                                <input class="form-check-input" type="checkbox" id="resolutionReviewAck">
                                                <label class="form-check-label small" for="resolutionReviewAck">
                                                    I've reviewed the above and confirm it's accurate.
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-pencil me-1"></i>Go Back & Edit</button>
                                            <button type="button" class="btn btn-outline-secondary" id="resolutionConfirmSubmitBtn" disabled><i class="bi bi-check-all me-1"></i>Confirm & Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                (function () {
                                    const form = document.getElementById('resolutionForm');
                                    const reviewBtn = document.getElementById('resolutionReviewBtn');
                                    if (!form || !reviewBtn) return;

                                    const ackCheckbox = document.getElementById('resolutionReviewAck');
                                    const confirmBtn = document.getElementById('resolutionConfirmSubmitBtn');
                                    const reviewModalEl = document.getElementById('resolutionReviewModal');

                                    function setConfirmReady(ready) {
                                        confirmBtn.disabled = !ready;
                                        confirmBtn.classList.toggle('btn-success', ready);
                                        confirmBtn.classList.toggle('btn-outline-secondary', !ready);
                                    }

                                    // Reset every time the modal opens — otherwise re-opening after
                                    // "Go Back & Edit" (or a previous confirm) could silently leave the
                                    // button green/enabled from a prior visit instead of a clean state.
                                    reviewModalEl.addEventListener('show.bs.modal', function () {
                                        ackCheckbox.checked = false;
                                        setConfirmReady(false);
                                    });

                                    ackCheckbox.addEventListener('change', function () {
                                        setConfirmReady(this.checked);
                                    });

                                    reviewBtn.addEventListener('click', function () {
                                        if (!form.reportValidity()) return;

                                        document.getElementById('reviewSummary').textContent = document.getElementById('summary').value.trim();
                                        document.getElementById('reviewActionsTaken').textContent = document.getElementById('actions_taken').value.trim();
                                        const fileCount = document.getElementById('evidence').files.length;
                                        document.getElementById('reviewEvidenceCount').textContent = fileCount > 0
                                            ? `${fileCount} file${fileCount === 1 ? '' : 's'} attached`
                                            : 'None attached — submitting without photo/document evidence.';

                                        const modal = new bootstrap.Modal(reviewModalEl);
                                        modal.show();
                                    });

                                    confirmBtn.addEventListener('click', function () {
                                        if (!ackCheckbox.checked) return;
                                        this.disabled = true;
                                        form.submit();
                                    });
                                })();
                            </script>
                        @else
                            @php
                                $userId = auth()->id();
                                $agencyResolution = null;
                                foreach($incident->resolutions as $res) {
                                    if ($res->resolver && $res->resolver->id === $userId) {
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
                                            <form action="{{ route('personnel.incidents.resolution.update', [$incident->id, $agencyResolution->id]) }}" method="POST" enctype="multipart/form-data">
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

                        <div class="alert alert-info small mb-3">
                            Printable copy requests are managed by MDRRMO administration. Personnel accounts do not create printable report requests directly.
                        </div>

                        @php
                            $userId = auth()->id();
                            $agencyResolution = null;
                            foreach($incident->resolutions as $res) {
                                if ($res->resolver && $res->resolver->id === $userId) {
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
        <script>
            window.RANIAG_GPS = @json($gpsConfig ?? [
                'max_captures' => config('raniag.gps_camera.max_captures'),
                'jpeg_quality' => config('raniag.gps_camera.jpeg_quality'),
                'geolocation' => [
                    'enableHighAccuracy' => config('raniag.geolocation.enable_high_accuracy'),
                    'timeout' => config('raniag.geolocation.timeout_ms'),
                    'maximumAge' => config('raniag.geolocation.maximum_age_ms'),
                ],
            ]);
        </script>
        <script src="{{ asset('js/gps-camera.js') }}?v={{ @filemtime(public_path('js/gps-camera.js')) }}"></script>
        <script src="{{ asset('js/field-outbox.js') }}?v={{ @filemtime(public_path('js/field-outbox.js')) }}"></script>
        <script src="{{ asset('js/raniag-location-ping.js') }}?v={{ @filemtime(public_path('js/raniag-location-ping.js')) }}"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.RANIAG_LocationPing?.start({
                url: @json(route('personnel.location_ping')),
                phaseUrl: @json(route('personnel.incidents.field_phase', $incident)),
                phase: @json(optional($myAssignment ?? null)->field_phase),
                scene: { lat: {{ $incident->latitude ?: 'null' }}, lng: {{ $incident->longitude ?: 'null' }} },
                getPhase() {
                    return document.getElementById('field-phase-strip')?.dataset?.fieldPhase || @json(optional($myAssignment ?? null)->field_phase);
                },
            });
        });
        </script>
        @if ($incident->latitude && $incident->longitude)
                        <style>
                /* Pin styling now lives in the shared .raniag-marker-pin class
                   (public/css/public.css) — see public/js/incident-map-icons.js. */
            </style>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script src="{{ asset('js/incident-map-icons.js') }}?v={{ @filemtime(public_path('js/incident-map-icons.js')) }}"></script>
            <script src="{{ asset('js/raniag-mapbox.js') }}?v={{ @filemtime(public_path('js/raniag-mapbox.js')) }}"></script>
            <script src="{{ asset('js/raniag-dispatch-map.js') }}?v={{ @filemtime(public_path('js/raniag-dispatch-map.js')) }}"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const withinJurisdiction = @json($incident->meta['within_jurisdiction'] ?? null);
                    window.RANIAG_DispatchMap?.init({
                        el: 'personnel-incident-map',
                        lat: {{ $incident->latitude }},
                        lng: {{ $incident->longitude }},
                        map: @json(config('raniag.map')),
                        icon: @json($incident->incidentType?->icon),
                        color: @json($incident->incidentType?->color),
                        outsideJurisdiction: withinJurisdiction === false,
                        scenePopup: withinJurisdiction === false ? 'Incident Location (Outside AOR)' : 'Incident Location',
                        unitsUrl: @json(route('personnel.incidents.live_units', $incident)),
                        selfLabel: @json(auth()->user()->agency?->name ?? auth()->user()->name ?? 'You'),
                        statusEl: 'personnel-units-status',
                        pollMs: 5000,
                    });
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
