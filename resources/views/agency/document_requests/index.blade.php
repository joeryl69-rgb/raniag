<x-app-layout>
    <x-slot name="header">{{ __('My Document Requests') }}</x-slot>

    <div class="card raniag-card shadow-sm border-0">
        <div class="card-header raniag-card-header border-0 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 flex-wrap">
                <div>
                    <p class="small text-muted mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Track printable requests and approval updates.</p>
                </div>

                <form method="GET" action="{{ route('agency.document_requests.index') }}" class="d-flex gap-2 align-items-center flex-wrap" data-loading-message="Filtering document requests...">
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width: 190px;" placeholder="Search tracking # or note">
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
                    <input type="date" name="date_from" value="{{ request('date_from') }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" style="width: 145px;" title="From date">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" max="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" style="width: 145px;" title="To date">
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
                    <div class="col-12 d-none" id="singleSectionPicker">
                        @include('agency.document_requests._section_picker', ['idPrefix' => 'single'])
                    </div>
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
                    <div class="col-12 d-none" id="bulkSectionPicker">
                        @include('agency.document_requests._section_picker', ['idPrefix' => 'bulk'])
                    </div>
                </form>

                {{-- Incomplete-document confirmation modal (shared by single & bulk) --}}
                <div class="modal fade" id="incompleteDocsModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Incomplete Report(s)</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small mb-2">The following selected report(s) are missing some required documents:</p>
                                <ul id="incompleteDocsList" class="small mb-0"></ul>
                                <p class="small text-muted mt-2 mb-0">You can still request a printable copy now, or cancel and wait until the file is complete.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-sm btn-primary" id="incompleteDocsContinueBtn">Continue Anyway</button>
                            </div>
                        </div>
                    </div>
                </div>

                @push('scripts')
                <script>
                    (function () {
                        var incompleteIncidents = @json($incompleteIncidents ?? []);
                        var documentAvailability = @json($documentAvailability ?? []);
                        var FORM_TYPES = ['call_taker_form', 'dispatch_form', 'narrative_report', 'endorsement_sheet'];
                        var sectionLabels = {
                            call_taker_form: 'Call Taker Form',
                            dispatch_form: 'Dispatch Form',
                            narrative_report: 'Narrative Report',
                            endorsement_sheet: 'Endorsement Sheet'
                        };

                        // Lets the agency see, per report, which form documents are on file
                        // right now — and makes it impossible to check a box for one that
                        // isn't, instead of warning about it after the fact.
                        function applyAvailability(idPrefix, incidentIds) {
                            var unavailable = [];
                            FORM_TYPES.forEach(function (type) {
                                var availableForAll = incidentIds.every(function (id) {
                                    var map = documentAvailability[id];
                                    return map ? !!map[type] : true;
                                });
                                var input = document.getElementById(idPrefix + '_section_' + type);
                                var wrap = document.getElementById(idPrefix + '_section_' + type + '_wrap');
                                if (!input || !wrap) return;
                                input.disabled = !availableForAll;
                                wrap.classList.toggle('opacity-50', !availableForAll);
                                if (!availableForAll) {
                                    input.checked = false;
                                    unavailable.push(sectionLabels[type]);
                                }
                            });
                            var note = document.getElementById(idPrefix + '_availability_note');
                            if (!note) return;
                            if (unavailable.length) {
                                note.textContent = 'Not yet available for the selected report(s): ' + unavailable.join(', ') + '.';
                                note.classList.remove('d-none');
                            } else {
                                note.classList.add('d-none');
                            }
                        }

                        var incompleteModalEl = document.getElementById('incompleteDocsModal');
                        var incompleteModal = new bootstrap.Modal(incompleteModalEl);
                        var incompleteList = document.getElementById('incompleteDocsList');
                        var continueBtn = document.getElementById('incompleteDocsContinueBtn');
                        var pendingSubmitForm = null;

                        function missingFor(ids) {
                            var out = {};
                            ids.forEach(function (id) {
                                if (incompleteIncidents[id] && incompleteIncidents[id].missing.length) {
                                    out[id] = incompleteIncidents[id];
                                }
                            });
                            return out;
                        }

                        // Intercepts a form submit; if any selected incident has missing
                        // documents, shows a warning modal and lets the agency choose to
                        // continue or cancel instead of silently submitting.
                        function guardSubmit(form, getIncidentIds) {
                            form.addEventListener('submit', function (e) {
                                if (form.dataset.confirmed === '1') {
                                    form.dataset.confirmed = '';
                                    return;
                                }
                                // Specific docs already can't be checked unless available
                                // (see applyAvailability). Only the "everything" default
                                // (nothing checked) still needs the best-effort warning.
                                if (form.querySelectorAll('input[name="requested_sections[]"]:checked').length > 0) return;
                                var missing = missingFor(getIncidentIds());
                                var trackingLabels = Object.keys(missing);
                                if (trackingLabels.length === 0) return;

                                e.preventDefault();
                                e.stopImmediatePropagation();
                                incompleteList.innerHTML = '';
                                trackingLabels.forEach(function (id) {
                                    var li = document.createElement('li');
                                    var info = missing[id];
                                    li.textContent = info.tracking + ': missing ' + info.missing.join(', ');
                                    incompleteList.appendChild(li);
                                });
                                pendingSubmitForm = form;
                                incompleteModal.show();
                            });
                        }

                        continueBtn.addEventListener('click', function () {
                            incompleteModal.hide();
                            if (pendingSubmitForm) {
                                pendingSubmitForm.dataset.confirmed = '1';
                                pendingSubmitForm.requestSubmit ? pendingSubmitForm.requestSubmit() : pendingSubmitForm.submit();
                                pendingSubmitForm = null;
                            }
                        });

                        // ---- SINGLE MODE ----
                        var sel = document.getElementById('quickPrintIncident');
                        var singleForm = document.getElementById('quickPrintForm');
                        var singleBtn = document.getElementById('quickPrintSubmit');

                        sel.addEventListener('change', function () {
                            singleForm.action = sel.value;
                            singleBtn.disabled = !sel.value;
                            document.getElementById('singleSectionPicker').classList.toggle('d-none', !sel.value);
                            var opt = sel.options[sel.selectedIndex];
                            var id = opt ? opt.getAttribute('data-incident-id') : null;
                            applyAvailability('single', id ? [id] : []);
                        });

                        guardSubmit(singleForm, function () {
                            var opt = sel.options[sel.selectedIndex];
                            var id = opt ? opt.getAttribute('data-incident-id') : null;
                            return id ? [id] : [];
                        });

                        // ---- BULK MODE ----
                        var bulkBtn = document.getElementById('bulkPrintSubmit');
                        var bulkCounter = document.getElementById('bulkCounter');

                        function refreshBulkBtn() {
                            var ids = Array.prototype.map.call(
                                document.querySelectorAll('.bulk-incident-checkbox:checked'),
                                function (cb) { return cb.value; }
                            );
                            bulkCounter.textContent = ids.length + ' selected';
                            bulkBtn.disabled = ids.length === 0;
                            document.getElementById('bulkSectionPicker').classList.toggle('d-none', ids.length === 0);
                            applyAvailability('bulk', ids);
                        }

                        guardSubmit(document.getElementById('bulkPrintForm'), function () {
                            return Array.prototype.map.call(
                                document.querySelectorAll('.bulk-incident-checkbox:checked'),
                                function (cb) { return cb.value; }
                            );
                        });

                        document.querySelectorAll('.bulk-incident-checkbox').forEach(function (cb) {
                            cb.addEventListener('change', refreshBulkBtn);
                        });

                        // ---- MODE TOGGLE ----
                        var bulkForm = document.getElementById('bulkPrintForm');
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
                @endpush
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
