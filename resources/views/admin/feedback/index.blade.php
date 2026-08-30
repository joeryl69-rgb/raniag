<x-app-layout>
    <x-slot name="header">
        {{ __('Feedback & Concerns') }}
    </x-slot>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <p class="small text-muted mb-0">Messages submitted by the public through the RANIAG landing page.</p>
    <div class="d-flex gap-2">
        <span class="badge bg-danger">{{ $counts['new'] }} New</span>
        <span class="badge bg-warning text-dark">{{ $counts['reviewed'] }} Reviewed</span>
        <span class="badge bg-success">{{ $counts['resolved'] }} Resolved</span>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="all">All statuses</option>
                <option value="new" @selected(request('status')==='new')>New</option>
                <option value="reviewed" @selected(request('status')==='reviewed')>Reviewed</option>
                <option value="resolved" @selected(request('status')==='resolved')>Resolved</option>
            </select>
            <select name="category" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="all">All categories</option>
                <option value="feedback" @selected(request('category')==='feedback')>General Feedback</option>
                <option value="concern" @selected(request('category')==='concern')>Concern</option>
                <option value="suggestion" @selected(request('category')==='suggestion')>Suggestion</option>
                <option value="bug" @selected(request('category')==='bug')>Bug Report</option>
            </select>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse($submissions as $item)
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi {{ $item->categoryIcon() }} fs-5 text-primary mt-1"></i>
                        <div>
                            <div class="fw-semibold">{{ $item->subject }}</div>
                            <div class="small text-muted">
                                {{ $item->categoryLabel() }}
                                @if($item->submitter_name) · {{ $item->submitter_name }} @endif
                                @if($item->submitter_email) · {{ $item->submitter_email }} @endif
                                · {{ $item->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    <span class="badge {{ ['new' => 'bg-danger', 'reviewed' => 'bg-warning text-dark', 'resolved' => 'bg-success'][$item->status] }}">
                        {{ ucfirst($item->status) }}
                    </span>
                </div>
                <p class="mt-2 mb-2 small">{{ $item->message }}</p>

                @if($item->admin_reply)
                    <div class="border-start border-3 border-primary ps-3 my-2 small bg-light rounded-2 py-2">
                        <div class="text-muted mb-1">
                            <i class="bi bi-reply-fill me-1"></i>Replied {{ $item->replied_at?->diffForHumans() }} by {{ $item->replier?->name ?? '—' }}
                        </div>
                        {!! $item->admin_reply !!}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.feedback.update', $item) }}" class="d-flex flex-wrap gap-2 align-items-center">
                    @csrf
                    @method('PUT')
                    <select name="status" class="form-select form-select-sm" style="width:auto;">
                        <option value="new" @selected($item->status==='new')>New</option>
                        <option value="reviewed" @selected($item->status==='reviewed')>Reviewed</option>
                        <option value="resolved" @selected($item->status==='resolved')>Resolved</option>
                    </select>
                    <input type="text" name="admin_notes" class="form-control form-control-sm" style="max-width:320px;" placeholder="Internal note (optional)" value="{{ $item->admin_notes }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                    @if($item->reviewed_at)
                        <span class="small text-muted">Last reviewed {{ $item->reviewed_at->diffForHumans() }} by {{ $item->reviewer?->name ?? '—' }}</span>
                    @endif

                    @if($item->submitter_email)
                        <button type="button" class="btn btn-sm btn-primary ms-auto" onclick="openReplyModal({{ $item->id }}, @js($item->subject), @js($item->submitter_email), @js($item->admin_reply ?? ''))">
                            <i class="bi bi-envelope-fill me-1"></i>{{ $item->admin_reply ? 'Edit & Resend Reply' : 'Reply by Email' }}
                        </button>
                    @else
                        <span class="ms-auto small text-muted"><i class="bi bi-envelope-slash me-1"></i>No email provided</span>
                    @endif
                </form>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No feedback submissions yet.
            </div>
        @endforelse
    </div>
</div>

<div class="mt-3">{{ $submissions->links() }}</div>

{{-- Reply modal: full rich-text editor (bold/italic/underline, headings,
     lists, links, blockquote) so the admin's response reads like a proper
     formatted email, not a plain textarea. --}}
<div class="modal fade" id="replyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="replyForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-envelope-fill me-2 text-primary"></i>Reply to <span id="replySubject" class="fw-bold ms-1"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Sending to <strong id="replyEmail"></strong></p>
                    <div id="replyEditor" style="min-height:220px; background:#fff;"></div>
                    <input type="hidden" name="admin_reply" id="replyHtmlInput">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill me-1"></i>Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
    let replyQuill = null;
    function getReplyQuill() {
        if (!replyQuill) {
            replyQuill = new Quill('#replyEditor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['blockquote', 'link'],
                        ['clean'],
                    ],
                },
            });
        }
        return replyQuill;
    }

    function openReplyModal(id, subject, email, existingHtml) {
        document.getElementById('replySubject').textContent = subject;
        document.getElementById('replyEmail').textContent = email;
        document.getElementById('replyForm').action = `/admin/feedback/${id}/reply`;
        const quill = getReplyQuill();
        quill.root.innerHTML = existingHtml || '';
        new bootstrap.Modal(document.getElementById('replyModal')).show();
    }

    document.getElementById('replyForm').addEventListener('submit', function () {
        document.getElementById('replyHtmlInput').value = getReplyQuill().root.innerHTML;
    });
</script>
@endpush
</x-app-layout>
