<x-app-layout>
    <x-slot name="header">{{ __('My Document Requests') }}</x-slot>

    <div class="card shadow-sm border-0" style="border-radius: 1rem; border: 1px solid #e7f1ea;">
        <div class="card-header bg-white border-0 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 flex-wrap">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i>My Document Requests</h5>
                    <p class="small text-muted mb-0 mt-1">Track printable requests and approval updates</p>
                </div>

                <form method="GET" action="{{ route('agency.document_requests.index') }}" class="d-flex gap-2 align-items-center flex-wrap" data-loading-message="Filtering document requests...">
                    @php
                        $currentStatus = request()->query('status', 'all');
                        $currentType = request()->query('request_type', 'all');
                    @endphp
                    <select name="status" class="form-select form-select-sm" style="width: 150px;">
                        <option value="all" @selected($currentStatus === 'all')>All Statuses</option>
                        <option value="pending" @selected($currentStatus === 'pending')>Pending</option>
                        <option value="approved" @selected($currentStatus === 'approved')>Approved</option>
                        <option value="sent" @selected($currentStatus === 'sent')>Sent</option>
                        <option value="failed" @selected($currentStatus === 'failed')>Failed</option>
                        <option value="rejected" @selected($currentStatus === 'rejected')>Rejected</option>
                        <option value="archived" @selected($currentStatus === 'archived')>Archived</option>
                    </select>
                    <select name="request_type" class="form-select form-select-sm" style="width: 140px;">
                        <option value="all" @selected($currentType === 'all')>All Types</option>
                        <option value="single" @selected($currentType === 'single')>Single</option>
                        <option value="bulk" @selected($currentType === 'bulk')>Bulk</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body border-bottom bg-light-subtle py-3" id="quickPrintCard">
            @if($eligibleIncidents->isEmpty())
                <p class="small text-muted mb-0"><i class="bi bi-check2-circle me-1"></i>No resolved reports awaiting a printable request right now.</p>
            @else
                <div class="btn-group btn-group-sm mb-3" role="group">
                    <button type="button" class="btn btn-primary" id="modeSingleBtn">Single Request</button>
                    <button type="button" class="btn btn-outline-primary" id="modeBulkBtn">Bulk Request</button>
                </div>

                {{-- SINGLE MODE --}}
                <form method="POST" id="quickPrintForm" action="#" class="row gy-2 gx-2 align-items-end">
                    @csrf
                    <input type="hidden" name="request_type" value="single">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Resolved Report</label>
                        <select id="quickPrintIncident" class="form-select form-select-sm" required>
                            <option value="">Select a resolved report…</option>
                            @foreach($eligibleIncidents as $inc)
                                <option value="{{ route('agency.incidents.print_requests.store', $inc->id) }}" data-incident-id="{{ $inc->id }}">{{ $inc->tracking_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-1">Note (optional)</label>
                        <input type="text" name="request_note" class="form-control form-control-sm" maxlength="1000" placeholder="What do you need in the printable copy?">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100" id="quickPrintSubmit" disabled>
                            <i class="bi bi-send me-1"></i>Request
                        </button>
                    </div>
                    <div class="col-12 d-none" id="singleSectionsWrap"></div>
                </form>

                {{-- BULK MODE --}}
                <form method="POST" id="bulkPrintForm" action="{{ route('agency.document_requests.bulk_store') }}" class="row gy-2 gx-2 align-items-end d-none">
                    @csrf
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small mb-0">Resolved Reports</label>
                            <span class="small text-muted" id="bulkCounter">0 selected</span>
                        </div>
                        <div class="border rounded p-2 d-flex flex-wrap gap-2" style="max-height: 140px; overflow-y: auto;">
                            @foreach($eligibleIncidents as $inc)
                                <input type="checkbox" class="btn-check bulk-incident-checkbox" name="incident_ids[]" value="{{ $inc->id }}" id="bulk_inc_{{ $inc->id }}" autocomplete="off">
                                <label class="btn btn-outline-secondary btn-sm rounded-pill px-3" for="bulk_inc_{{ $inc->id }}">{{ $inc->tracking_number }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small mb-1">Note (optional, applies to all selected)</label>
                        <input type="text" name="request_note" class="form-control form-control-sm" maxlength="1000" placeholder="What do you need in the printable copies?">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-sm btn-primary w-100" id="bulkPrintSubmit" disabled>
                            <i class="bi bi-send me-1"></i>Request Selected
                        </button>
                    </div>
                    <div class="col-12 d-none" id="bulkSectionsWrap"></div>
                </form>

                {{-- SHARED "Include in printable copy" TEMPLATE (cloned into whichever mode is active) --}}
                <template id="sectionsTemplate">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                        <label class="form-label small mb-0">Include in printable copy</label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted section-counter"></span>
                            <button type="button" class="btn btn-link btn-sm p-0 select-all-sections">Select All</button>
                            <span class="text-muted">|</span>
                            <button type="button" class="btn btn-link btn-sm p-0 deselect-all-sections">Deselect All</button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 section-checkbox-group">
                        @php
                            $sectionOptions = [
                                'incident_details' => ['Incident Details', 'Location, type, priority, and reporter info'],
                                'narrative' => ['Narrative', "Reporter's original account of the incident"],
                                'resolutions' => ['Resolution Notes', 'How the case was closed out'],
                                'status_timeline' => ['Status Timeline', 'Full history of status changes'],
                                'evidence_photos' => ['Evidence Photos', 'Uploaded photos attached to the case'],
                                'call_taker_form' => ['Call Taker Form', 'Details logged by the receiving call taker'],
                                'dispatch_form' => ['Dispatch Form', 'Assignment and dispatch record'],
                                'narrative_report' => ['Narrative Report', "Responding agency's written report"],
                                'endorsement_sheet' => ['Endorsement Sheet', 'Formal endorsement for referral/filing'],
                            ];
                        @endphp
                        @foreach ($sectionOptions as $value => [$label, $tip])
                            <input type="checkbox" class="btn-check section-checkbox" name="requested_sections[]" value="{{ $value }}" id="__PREFIX___{{ $value }}" checked autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="__PREFIX___{{ $value }}" title="{{ $tip }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    <div class="form-text">All content is included by default. Uncheck anything you don't need.</div>
                    <div class="text-danger small d-none section-required-warning">Select at least one section to include.</div>
                </template>

                <script>
                    (function () {
                        var tpl = document.getElementById('sectionsTemplate');

                        function mountSections(wrapEl, prefix) {
                            wrapEl.innerHTML = tpl.innerHTML.replaceAll('__PREFIX__', prefix);
                            wrapEl.classList.remove('d-none');
                            return wrapEl;
                        }

                        function wireSections(wrapEl, onChange) {
                            var checkboxes = wrapEl.querySelectorAll('.section-checkbox');
                            var counter = wrapEl.querySelector('.section-counter');
                            var warning = wrapEl.querySelector('.section-required-warning');

                            function refresh() {
                                var checked = wrapEl.querySelectorAll('.section-checkbox:checked').length;
                                counter.textContent = checked + ' of ' + checkboxes.length + ' sections included';
                                warning.classList.toggle('d-none', checked > 0);
                                onChange(checked);
                            }
                            checkboxes.forEach(function (cb) { cb.addEventListener('change', refresh); });
                            wrapEl.querySelector('.select-all-sections').addEventListener('click', function () {
                                checkboxes.forEach(function (cb) { cb.checked = true; }); refresh();
                            });
                            wrapEl.querySelector('.deselect-all-sections').addEventListener('click', function () {
                                checkboxes.forEach(function (cb) { cb.checked = false; }); refresh();
                            });
                            refresh();
                        }

                        // ---- SINGLE MODE ----
                        var sel = document.getElementById('quickPrintIncident');
                        var singleForm = document.getElementById('quickPrintForm');
                        var singleBtn = document.getElementById('quickPrintSubmit');
                        var singleWrap = document.getElementById('singleSectionsWrap');
                        var singleSectionsChecked = 0;

                        function refreshSingleBtn() {
                            singleBtn.disabled = !sel.value || singleSectionsChecked === 0;
                        }

                        sel.addEventListener('change', function () {
                            singleForm.action = sel.value;
                            if (sel.value && singleWrap.classList.contains('d-none')) {
                                mountSections(singleWrap, 'single');
                                wireSections(singleWrap, function (checked) {
                                    singleSectionsChecked = checked;
                                    refreshSingleBtn();
                                });
                            } else if (!sel.value) {
                                singleWrap.classList.add('d-none');
                                singleWrap.innerHTML = '';
                            }
                            refreshSingleBtn();
                        });

                        // ---- BULK MODE ----
                        var bulkForm = document.getElementById('bulkPrintForm');
                        var bulkBtn = document.getElementById('bulkPrintSubmit');
                        var bulkWrap = document.getElementById('bulkSectionsWrap');
                        var bulkCounter = document.getElementById('bulkCounter');
                        var bulkSectionsChecked = 0;
                        var bulkSectionsWired = false;

                        function refreshBulkBtn() {
                            var incidentsChecked = document.querySelectorAll('.bulk-incident-checkbox:checked').length;
                            bulkCounter.textContent = incidentsChecked + ' selected';
                            if (incidentsChecked > 0 && !bulkSectionsWired) {
                                mountSections(bulkWrap, 'bulk');
                                wireSections(bulkWrap, function (checked) {
                                    bulkSectionsChecked = checked;
                                    bulkBtn.disabled = incidentsChecked === 0 || bulkSectionsChecked === 0;
                                });
                                bulkSectionsWired = true;
                            } else if (incidentsChecked === 0 && bulkSectionsWired) {
                                bulkWrap.classList.add('d-none');
                                bulkWrap.innerHTML = '';
                                bulkSectionsWired = false;
                            }
                            bulkBtn.disabled = incidentsChecked === 0 || (bulkSectionsWired && bulkSectionsChecked === 0);
                        }

                        document.querySelectorAll('.bulk-incident-checkbox').forEach(function (cb) {
                            cb.addEventListener('change', refreshBulkBtn);
                        });

                        // ---- MODE TOGGLE ----
                        var modeSingleBtn = document.getElementById('modeSingleBtn');
                        var modeBulkBtn = document.getElementById('modeBulkBtn');
                        modeSingleBtn.addEventListener('click', function () {
                            singleForm.classList.remove('d-none');
                            bulkForm.classList.add('d-none');
                            modeSingleBtn.classList.replace('btn-outline-primary', 'btn-primary');
                            modeBulkBtn.classList.replace('btn-primary', 'btn-outline-primary');
                        });
                        modeBulkBtn.addEventListener('click', function () {
                            bulkForm.classList.remove('d-none');
                            singleForm.classList.add('d-none');
                            modeBulkBtn.classList.replace('btn-outline-primary', 'btn-primary');
                            modeSingleBtn.classList.replace('btn-primary', 'btn-outline-primary');
                        });

                        // ---- RESUBMIT (event delegation — table renders after this script) ----
                        document.addEventListener('click', function (e) {
                            var b = e.target.closest('.resubmit-btn');
                            if (!b) return;
                            var id = b.getAttribute('data-incident-id');
                            var opt = sel.querySelector('option[data-incident-id="' + id + '"]');
                            if (!opt) {
                                alert('This report is no longer eligible for resubmission (it may already have a pending request).');
                                return;
                            }
                            modeSingleBtn.click();
                            sel.value = opt.value;
                            sel.dispatchEvent(new Event('change'));
                            document.getElementById('quickPrintCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
                        });
                    })();
                </script>
            @endif
        </div>
        <div class="card-body">
            @if($documentRequests->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-x display-4"></i>
                    <p class="mt-2 mb-0">No document requests found.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="px-3 py-3">Tracking #</th>
                                <th class="py-3">Request Type</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Requested At</th>
                                <th class="py-3">Request Details</th>
                                <th class="px-3 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentRequests as $dr)
                                <tr>
                                    <td class="px-3 py-3 fw-bold text-primary">{{ $dr->incident->tracking_number ?? 'N/A' }}</td>
                                    <td class="py-3">{{ ucfirst($dr->request_type) }}</td>
                                    <td class="py-3">
                                        <span class="badge bg-primary-subtle text-primary">{{ ucfirst($dr->status) }}</span>
                                        @if($dr->archived_at)
                                            <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-archive me-1"></i>Archived</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-muted">{{ optional($dr->created_at)->format('M d, Y h:i A') }}</td>
                                    <td class="py-3">{{ $dr->request_note ?? ($dr->admin_comment ?? '-') }}</td>
                                    <td class="px-3 py-3 text-end">
                                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                                            @if($dr->generated_path)
                                                <a href="{{ Storage::disk('public')->url($dr->generated_path) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>View Printable PDF
                                                </a>
                                            @endif

                                            @if($dr->status === 'sent')
                                                <a href="https://mail.google.com/mail/u/0/#inbox" target="_blank" class="btn btn-sm btn-outline-primary">Check Gmail</a>
                                            @elseif($dr->status === 'rejected')
                                                <span class="text-danger small align-self-center me-1" title="{{ $dr->admin_comment ?? 'No reason provided.' }}">Rejected</span>
                                                @if($dr->incident_id)
                                                    <button type="button" class="btn btn-sm btn-outline-danger resubmit-btn" data-incident-id="{{ $dr->incident_id }}">
                                                        <i class="bi bi-arrow-repeat me-1"></i>Resubmit
                                                    </button>
                                                @endif
                                            @elseif(!$dr->generated_path)
                                                <span class="text-muted small align-self-center">Pending review</span>
                                            @endif

                                            @if(in_array($dr->status, ['sent', 'failed', 'rejected']))
                                                @if($dr->archived_at)
                                                    <form method="POST" action="{{ route('agency.document_requests.unarchive', $dr->id) }}">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up me-1"></i>Unarchive</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('agency.document_requests.archive', $dr->id) }}">
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-archive me-1"></i>Archive</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($documentRequests->hasPages())
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 px-1">
                        <small class="text-muted">Showing {{ $documentRequests->firstItem() }} to {{ $documentRequests->lastItem() }} of {{ $documentRequests->total() }} results</small>
                        <div>
                            {{ $documentRequests->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
