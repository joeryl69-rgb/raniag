<x-app-layout>
    <x-slot name="header">
        {{ $incident->tracking_number }}
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <style>
            #show-incident-map {
                /* filled by .case-map-frame absolute child */
            }
            .case-map-frame {
                width: 100%;
                aspect-ratio: 16 / 10;
                min-height: 220px;
                max-height: min(55vh, 420px);
                position: relative;
                overflow: hidden;
                border-radius: 0.5rem;
                border: 1px solid #dee2e6;
                background: #eef2f6;
            }
            .case-map-frame #show-incident-map,
            .case-map-frame .leaflet-container {
                position: absolute;
                inset: 0;
                width: 100% !important;
                height: 100% !important;
                z-index: 1;
                border: 0;
                border-radius: 0;
            }
            @media (max-width: 767.98px) {
                .case-map-frame { aspect-ratio: 4 / 3; max-height: 50vh; }
            }
            .case-unit-strip {
                display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
                padding: .55rem 0; font-size: .82rem; color: #64748b;
            }
            .case-unit-strip .live-dot {
                width: 8px; height: 8px; border-radius: 50%; background: #16a34a;
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
            <div class="card raniag-card rg-app-card mb-4">
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
            <div class="card raniag-card rg-app-card mb-4">
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
                                            @if (str_starts_with($ev->mime_type, 'image/'))
                                                <a href="{{ $ev->url() }}" class="js-lightbox" data-group="evidence-public-{{ $incident->id }}" data-caption="{{ $ev->original_filename }}">
                                                    <img src="{{ $ev->url() }}" class="card-img-top" alt="Evidence">
                                                </a>
                                            @elseif (str_starts_with((string) $ev->mime_type, 'video/'))
                                                <video src="{{ $ev->url() }}" class="w-100" style="max-height: 280px; background: #0f172a; object-fit: contain;" controls playsinline preload="metadata"></video>
                                            @else
                                                <div class="d-flex align-items-center justify-content-center bg-light text-secondary card-img-top" style="height: 150px;">
                                                    <i class="bi bi-file-earmark fs-1"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-body p-2 small">
                                            @if ($ev->is_gps_capture)
                                                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle mb-1">
                                                    <i class="bi bi-geo-alt-fill me-1"></i>{{ str_starts_with((string) $ev->mime_type, 'video/') ? 'GPS Video' : 'GPS Photo' }}
                                                </span>
                                            @endif
                                            <div class="text-truncate" title="{{ $ev->original_filename }}">{{ $ev->original_filename }}</div>
                                            <div class="text-muted">{{ number_format($ev->file_size / 1024, 1) }} KB</div>
                                            <a href="{{ $ev->url() }}" download class="btn btn-link btn-sm p-0 mt-1"><i class="bi bi-download me-1"></i>Download</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card raniag-card rg-app-card mb-4">
                <div class="card-header raniag-card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-folder2-open me-2 text-primary"></i>Paper forms</h5>
                        <div class="text-muted small mt-1">The four paper forms are filed under Case Documents. This screen stays on the live report.</div>
                    </div>
                    <a href="{{ route('admin.incident_documents.show', $incident) }}" class="btn btn-sm btn-outline-primary">Open case file</a>
                </div>
                <div class="card-body">
                    @php
                        $docs = $incident->incidentDocuments ?? collect();
                        $typeValue = function ($doc) {
                            return is_object($doc->document_type) ? $doc->document_type->value : $doc->document_type;
                        };
                    @endphp
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (\App\Enums\IncidentDocumentType::cases() as $docType)
                            @php $onFile = $docs->contains(fn ($doc) => $typeValue($doc) === $docType->value); @endphp
                            <span class="badge {{ $onFile ? 'text-bg-success' : 'text-bg-light border text-muted' }}">{{ $docType->label() }}</span>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-2">{{ $docs->unique(fn ($doc) => $typeValue($doc))->count() }} of {{ count(\App\Enums\IncidentDocumentType::cases()) }} form types on file.</div>
                </div>
            </div>
            <!-- Timeline Section -->
            <div class="card raniag-card rg-app-card mb-4">
                <div class="card-header raniag-card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Activity & Status Timeline</h5>
                </div>
                <div class="card-body">
                    @if ($incident->statusTimeline->isEmpty())
                        <p class="text-muted mb-0">No history available.</p>
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
            <div class="card raniag-card rg-app-card mb-4">
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

                    @if (! empty($incident->meta['needs_verification']))
                        <div class="alert alert-info py-2 px-3 mb-2 small">
                            <i class="bi bi-telephone-outbound me-1"></i>
                            Call-back verification queue — submitted without GPS photo evidence.
                            @if ($incident->safeReporterPhone())
                                Phone on file (encrypted). Contact the reporter to verify.
                            @endif
                        </div>
                    @endif

                    @if ($incident->latitude && $incident->longitude)
                        @php $withinJurisdiction = $incident->meta['within_jurisdiction'] ?? null; @endphp
                        @if ($withinJurisdiction === false)
                            <div class="alert alert-warning py-2 px-3 mb-2 small">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Pinned location is outside Pamplona municipality limits.
                            </div>
                        @endif
                        <div class="case-map-frame mb-2">
                            <div id="show-incident-map"></div>
                        </div>
                        <div class="case-unit-strip" id="dispatch-units-status">Loading live units…</div>
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
            <div class="card raniag-card rg-app-card mb-4">
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
                        @php
                            $identifiedWithoutGps = ! $incident->reporterSubmittedGpsCamera();
                            $reporterTexts = $identifiedWithoutGps ? $incident->reporterTextMessages() : collect();
                        @endphp
                        @if ($identifiedWithoutGps)
                            <div class="alert alert-warning d-flex gap-2 align-items-start small py-2" role="status">
                                <i class="bi bi-person-badge mt-1"></i>
                                <div>
                                    <strong>Reporter is not anonymous.</strong>
                                    No GPS camera photo or video was submitted, so they had to leave their contact details.
                                </div>
                            </div>
                        @endif
                        <dl class="mb-0">
                            <dt class="text-muted small">Name</dt>
                            <dd class="mb-2 text-dark fw-semibold">{{ $incident->reporter_name ?? 'N/A' }}</dd>
                            
                            <dt class="text-muted small">Phone Number</dt>
                            <dd class="mb-2 text-dark">{{ $incident->safeReporterPhone() ?? 'N/A' }}</dd>
                            
                            <dt class="text-muted small">Email Address</dt>
                            <dd class="mb-0 text-dark">{{ $incident->reporter_email ?? 'N/A' }}</dd>
                        </dl>
                        @if ($reporterTexts->isNotEmpty())
                            <h6 class="fw-bold mt-3 mb-2">Texts to the reporter</h6>
                            <div class="small border rounded p-2 bg-white" style="max-height:180px;overflow:auto;">
                                @foreach ($reporterTexts as $sms)
                                    <div class="mb-2">
                                        <div class="text-muted">{{ $sms->created_at?->timezone(config('app.timezone'))->format('M j, g:ia') }} · {{ $sms->status?->value ?? $sms->status }}</div>
                                        <div>{{ $sms->message }}</div>
                                        @if ($sms->thread_note)
                                            <div class="fst-italic text-muted">Staff note: {{ $sms->thread_note }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if ($identifiedWithoutGps && filled($incident->safeReporterPhone()))
                            <form method="POST" action="{{ route('admin.incidents.sms_reporter', $incident) }}" class="mt-3">
                                @csrf
                                <label class="form-label small fw-semibold" for="admin-reporter-sms">Text message to the reporter</label>
                                <p class="form-text mt-0 mb-2">Sent to the phone number on this report. The same message appears for the agency and personnel assigned to the case.</p>
                                <textarea id="admin-reporter-sms" name="message" class="form-control form-control-sm mb-2" rows="2" maxlength="480" required placeholder="We are on the way. Stay clear of the area."></textarea>
                                <label class="form-label small fw-semibold" for="admin-reporter-note">Staff note (optional)</label>
                                <input id="admin-reporter-note" type="text" name="thread_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Example: family is waiting at the chapel">
                                <button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-chat-dots me-1"></i>Send text message</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="card rg-console-card mb-4">
                <div class="card-header">
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
                                    <option value="outside_aor">Mark as Outside AOR (Refer Externally)</option>
                                    <option value="reject">Reject Report</option>
                                </select>
                                @if(($incident->meta['within_jurisdiction'] ?? null) === false)
                                    <div class="form-text text-warning">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Pinned location is outside Pamplona municipality limits — consider "Outside AOR" if another LGU/agency should handle this.
                                    </div>
                                @endif
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
                                <label for="notes" class="form-label" id="notes_label">Validation Comments</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Explain the decision or special directions..."></textarea>
                                <div class="form-text d-none" id="notes-required-hint">Required — state which agency/municipality this was referred to.</div>
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
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-warning text-dark">Active</span>
                                                @if ($assignment->isAcknowledged())
                                                    <span class="badge bg-info text-dark" title="Acknowledged {{ $assignment->acknowledged_at->diffForHumans() }}">
                                                        <i class="bi bi-check2-circle"></i> Acknowledged
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">Pending Acceptance</span>
                                                @endif
                                            </div>
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
                                                        @if(str_starts_with((string) $ev->mime_type, 'video/'))
                                                            <video src="{{ $ev->url() }}" class="img-fluid rounded border shadow-sm w-100" style="aspect-ratio: 4/3; object-fit: cover; background:#0f172a;" controls playsinline preload="metadata"></video>
                                                        @else
                                                        <a href="{{ $ev->url() }}" class="js-lightbox" data-group="evidence-assignment-{{ $assignment->id }}" data-caption="{{ $ev->original_filename }}">
                                                            @if(str_starts_with($ev->mime_type, 'image/'))
                                                                <img src="{{ $ev->url() }}" class="img-fluid rounded border shadow-sm" style="aspect-ratio: 4/3; object-fit: cover;" alt="Evidence">
                                                            @else
                                                                <div class="d-flex align-items-center justify-content-center bg-light text-secondary rounded border shadow-sm" style="aspect-ratio: 4/3;">
                                                                    <i class="bi bi-file-earmark fs-4"></i>
                                                                </div>
                                                            @endif
                                                        </a>
                                                        @endif
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

                            @if (! in_array($incident->status->value, ['resolved', 'closed', 'rejected', 'outside_aor'], true))
                                {{-- Re-dispatch: add another agency/personnel to a case that is already
                                     assigned/in-progress, e.g. when it turns out a second agency needs
                                     to coordinate. Reuses the same admin.incidents.validate(action=approve)
                                     endpoint the initial dispatch uses — that endpoint always creates new
                                     Assignment rows, it never required the incident to still be "received". --}}
                                <div class="mt-3 pt-3 border-top">
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#redispatchForm">
                                        <i class="bi bi-send-plus me-1"></i>Re-dispatch / Add Another Agency
                                    </button>
                                    <div class="collapse mt-3" id="redispatchForm">
                                        @if ($agencies->isEmpty() && $personnel->isEmpty())
                                            <div class="alert alert-light border small mb-0">
                                                <i class="bi bi-check-circle me-1 text-success"></i>Every active agency and personnel account has already been dispatched to this incident — there's no one left to add.
                                            </div>
                                        @else
                                        <form action="{{ route('admin.incidents.validate', $incident->id) }}" method="POST" id="redispatchFormEl">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <div class="mb-3">
                                                <label class="form-label">Select Additional Government Branch(es) or Personnel</label>
                                                <div class="border rounded-3 p-3 bg-white" style="max-height: 220px; overflow-y: auto;">
                                                    @foreach ($agencies as $agency)
                                                        <div class="form-check">
                                                            <input class="form-check-input redispatch-option" type="checkbox" name="assigned_agency_id[]" value="{{ $agency->id }}" id="redispatch_agency_{{ $agency->id }}">
                                                            <label class="form-check-label" for="redispatch_agency_{{ $agency->id }}">
                                                                {{ $agency->code }} ({{ $agency->name }})
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                    @if ($personnel->isNotEmpty())
                                                        <hr class="my-3">
                                                        <div class="fw-semibold mb-2">Internal Personnel</div>
                                                        @foreach ($personnel as $person)
                                                            <div class="form-check">
                                                                <input class="form-check-input redispatch-option" type="checkbox" name="assigned_personnel_id[]" value="{{ $person->id }}" id="redispatch_personnel_{{ $person->id }}">
                                                                <label class="form-check-label" for="redispatch_personnel_{{ $person->id }}">
                                                                    {{ $person->name }} @if($person->role_title) ({{ $person->role_title }}) @endif
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                <div class="form-text">Existing assignments are untouched — this only adds new agencies/personnel for coordination.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Dispatch Notes</label>
                                                <textarea class="form-control" name="notes" rows="2" placeholder="Reason for coordinating another agency..."></textarea>
                                            </div>
                                            {{-- Used to be enabled/blue no matter what was checked — nothing
                                                 stopped an admin from submitting with zero agencies/personnel
                                                 selected. It now starts disabled and only becomes clickable once
                                                 at least one checkbox above is actually checked. --}}
                                            <button type="submit" class="btn btn-primary btn-sm" id="redispatchSubmitBtn" disabled>
                                                <i class="bi bi-send-fill me-1"></i>Confirm Re-dispatch
                                            </button>
                                        </form>
                                        <script>
                                            (function () {
                                                const collapseEl = document.getElementById('redispatchForm');
                                                const formEl = document.getElementById('redispatchFormEl');
                                                const submitBtn = document.getElementById('redispatchSubmitBtn');
                                                if (!collapseEl || !formEl || !submitBtn) return;

                                                const options = formEl.querySelectorAll('.redispatch-option');

                                                function refreshSubmitState() {
                                                    const anyChecked = Array.from(options).some((cb) => cb.checked);
                                                    submitBtn.disabled = !anyChecked;
                                                }

                                                options.forEach((cb) => cb.addEventListener('change', refreshSubmitState));

                                                // "Static default" fix: every time this panel is collapsed back
                                                // closed, clear it out properly — uncheck every box, blank the
                                                // notes field, and disable the button again — instead of leaving
                                                // a stale selection sitting there the next time it's expanded.
                                                collapseEl.addEventListener('hidden.bs.collapse', function () {
                                                    formEl.reset();
                                                    refreshSubmitState();
                                                });

                                                refreshSubmitState();
                                            })();
                                        </script>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($incident->status->value === 'assigned')
                                <span class="badge bg-warning text-dark mt-2">Awaiting Agency Response</span>
                            @elseif ($incident->status->value === 'in_progress')
                                <span class="badge bg-info mt-2">Under Investigation</span>
                            @elseif ($incident->status->value === 'pending_info')
                                @php
                                    // The admin view had no branch at all for pending_info before this fix —
                                    // the request was invisible here and only surfaced buried inside the
                                    // Full History log below, with no way to answer it.
                                    $latestInfoRequest = $incident->statusUpdates
                                        ->where('to_status', \App\Enums\IncidentStatus::PendingInfo)
                                        ->sortByDesc('created_at')
                                        ->first();
                                @endphp
                                <span class="badge bg-warning text-dark mt-2 mb-2"><i class="bi bi-hourglass-split me-1"></i>Awaiting Info / Pending Request</span>
                                <div class="alert alert-warning small mb-3">
                                    <strong>Requested by agency:</strong><br>
                                    {{ $latestInfoRequest?->comment ?? 'The assigned agency marked this case as awaiting information.' }}
                                </div>
                                <form action="{{ route('admin.incidents.reply', $incident->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small">Reply</label>
                                        <textarea class="form-control" name="reply" rows="3" required maxlength="2000" placeholder="Answer the agency's request..."></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">Then</label>
                                        <select class="form-select form-select-sm" name="resume_investigation">
                                            <option value="0">Send reply, keep awaiting (agency asked for more)</option>
                                            <option value="1">Send reply and resume investigation now</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-reply-fill me-1"></i>Send Reply to Agency</button>
                                </form>
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
                    const lat = {{ $incident->latitude }};
                    const lng = {{ $incident->longitude }};
                    const mapConfig = @json(config('raniag.map'));
                    const withinJurisdiction = @json($incident->meta['within_jurisdiction'] ?? null);
                    const hazardZones = @json($incident->meta['hazard_zones'] ?? []);

                    const statusBox = document.getElementById('dispatch-units-status');
                    const mapEl = document.getElementById('show-incident-map');

                    if (Array.isArray(hazardZones) && hazardZones.length && statusBox?.parentNode) {
                        const hz = document.createElement('div');
                        hz.className = 'alert alert-warning py-2 px-3 small mb-2';
                        hz.innerHTML = '<strong>Inside hazard zone:</strong> ' +
                            hazardZones.map(z => (z.name || 'Zone') + (z.type ? ' (' + z.type + ')' : '')).join(', ');
                        statusBox.parentNode.insertBefore(hz, statusBox);
                    }

                    window.RANIAG_DispatchMap?.init({
                        el: 'show-incident-map',
                        lat,
                        lng,
                        map: mapConfig,
                        icon: @json($incident->incidentType->icon ?? null),
                        color: @json($incident->incidentType->color ?? null),
                        outsideJurisdiction: withinJurisdiction === false,
                        scenePopup: withinJurisdiction === false ? 'Incident Location (Outside AOR)' : 'Incident Location',
                        unitsUrl: @json(route('admin.incidents.live_units', $incident)),
                        statusEl: 'dispatch-units-status',
                        pollMs: 5000,
                    });
                });
            </script>
        @endif
        <script>
            function toggleValidationView() {
                const action = document.getElementById('validation_action').value;
                const container = document.getElementById('agency-select-container');
                const notes = document.getElementById('notes');
                const hint = document.getElementById('notes-required-hint');

                if (action === 'approve') {
                    container.classList.remove('d-none');
                } else {
                    container.classList.add('d-none');

                    // Clear checkboxes when switching away from approve.
                    container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                }

                if (action === 'outside_aor') {
                    notes.setAttribute('required', 'required');
                    hint.classList.remove('d-none');
                } else {
                    notes.removeAttribute('required');
                    hint.classList.add('d-none');
                }
            }
        </script>

    @endpush
</x-app-layout>
