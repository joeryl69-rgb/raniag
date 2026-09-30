<x-app-layout>
    <x-slot name="header">
        {{ __('Feedback & Concerns') }}
    </x-slot>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <p class="small text-muted mb-0">Support Center messages from the public landing page and from signed-in agency/personnel accounts.</p>
    <div class="d-flex gap-2">
        <span class="badge bg-danger">{{ $counts['new'] }} New</span>
        <span class="badge bg-warning text-dark">{{ $counts['reviewed'] }} Reviewed</span>
        <span class="badge bg-success">{{ $counts['resolved'] }} Resolved</span>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <x-filters.toolbar search-placeholder="Search subject, message, name, email" :action="route('admin.feedback.index')">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select" data-filter-default="all">
                    <option value="all" @selected(request('status', 'all') === 'all')>All statuses</option>
                    <option value="new" @selected(request('status')==='new')>New</option>
                    <option value="reviewed" @selected(request('status')==='reviewed')>Reviewed</option>
                    <option value="resolved" @selected(request('status')==='resolved')>Resolved</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Category</label>
                <select name="category" class="form-select" data-filter-default="all">
                    <option value="all" @selected(request('category', 'all') === 'all')>All categories</option>
                    @foreach(\App\Models\FeedbackSubmission::filterableCategories() as $key => $cat)
                        <option value="{{ $key }}" @selected(request('category')===$key)>{{ $cat['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Source</label>
                <select name="source" class="form-select" data-filter-default="all">
                    <option value="all" @selected(request('source', 'all') === 'all')>All sources</option>
                    <option value="public" @selected(request('source')==='public')>Public (Landing Page)</option>
                    <option value="agency" @selected(request('source')==='agency')>Support Center (Agency/Personnel)</option>
                </select>
            </div>
        </x-filters.toolbar>
    </div>
</div>

<div data-live-refresh data-live-refresh-target="#rg-feedback-list" data-live-refresh-interval="4000">
    <div class="rg-fb-list" id="rg-feedback-list">
        @forelse($submissions as $item)
            <article class="rg-fb-case is-{{ $item->status }}">
                <header class="rg-fb-head">
                    <i class="bi {{ $item->categoryIcon() }}" aria-hidden="true"></i>
                    <div>
                        <h2>{{ $item->subject }}</h2>
                        <p>
                            {{ $item->categoryLabel() }}
                            @if($item->isFromAgency())
                                · Support Center{{ $item->agency ? ' · '.$item->agency->name : '' }}
                            @else
                                · Public landing page
                            @endif
                            @if($item->submitter_name) · {{ $item->submitter_name }} @endif
                            @if($item->submitter_email) · {{ $item->submitter_email }} @endif
                            · {{ $item->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="rg-fb-state">{{ ucfirst($item->status) }}</span>
                </header>

                <p class="rg-fb-message">{{ $item->message }}</p>

                @if($item->admin_reply)
                    <div class="rg-fb-reply">
                        <div class="rg-fb-reply-meta">
                            <i class="bi bi-reply-fill"></i>
                            Email sent {{ $item->replied_at?->diffForHumans() }} by {{ $item->replier?->name ?? '—' }}
                        </div>
                        <div class="rg-fb-reply-body">{!! $item->admin_reply !!}</div>
                    </div>
                @endif

                <div class="rg-fb-desk">
                    <form method="POST" action="{{ route('admin.feedback.update', $item) }}">
                        @csrf
                        @method('PUT')
                        <fieldset class="rg-fb-status">
                            <legend>Update status</legend>
                            @foreach(['new' => 'New', 'reviewed' => 'Reviewed', 'resolved' => 'Resolved'] as $value => $label)
                                <label class="rg-fb-choice">
                                    <input type="radio" name="status" value="{{ $value }}" @checked($item->status === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </fieldset>
                        <label class="rg-fb-note">
                            <span>Internal note <em>not emailed</em></span>
                            <input type="text" name="admin_notes" maxlength="2000" value="{{ $item->admin_notes }}" placeholder="Visible only to staff">
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                    <div class="rg-fb-mail">
                        @if($item->reviewed_at)
                            <p>Last saved {{ $item->reviewed_at->diffForHumans() }} by {{ $item->reviewer?->name ?? '—' }}</p>
                        @endif
                        @if($item->submitter_email)
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openReplyModal({{ $item->id }}, @js($item->subject), @js($item->submitter_email), @js($item->admin_reply ?? ''))">
                                <i class="bi bi-envelope"></i>{{ $item->admin_reply ? 'Edit and resend email' : 'Email a reply' }}
                            </button>
                        @else
                            <p><i class="bi bi-envelope-slash"></i> No email on this message, so a reply cannot be sent.</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No feedback submissions yet.
            </div>
        @endforelse
    </div>
</div>

@if($submissions->hasPages())
<div class="mt-3">
    {{ $submissions->links('pagination::bootstrap-5') }}
</div>
@endif

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

@push('styles')
<style>
.rg-fb-list { display: grid; gap: 14px; }
.rg-fb-case {
    background: #fff;
    border: 1px solid var(--raniag-border, #dde5ea);
    border-left: 4px solid #dc3545;
    border-radius: 14px;
    box-shadow: var(--raniag-card-shadow, 0 0.5rem 1.5rem rgba(15, 23, 42, 0.08));
    padding: 16px 16px 14px;
}
.rg-fb-case.is-reviewed { border-left-color: #f6a723; }
.rg-fb-case.is-resolved { border-left-color: #198754; }
.rg-fb-head { display: flex; align-items: flex-start; gap: 12px; }
.rg-fb-head > i {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: var(--raniag-primary-light, #e7f0fd);
    color: var(--raniag-primary, #0b5ed7);
    flex-shrink: 0;
}
.rg-fb-head h2 { margin: 0; font-size: 1rem; font-weight: 800; }
.rg-fb-head p { margin: 2px 0 0; color: #5b6780; font-size: .82rem; }
.rg-fb-state {
    margin-left: auto;
    border-radius: 999px;
    padding: .2rem .7rem;
    font-size: .75rem;
    font-weight: 800;
    background: #fde8ea;
    color: #b42318;
}
.is-reviewed .rg-fb-state { background: #fff4d6; color: #8a5a00; }
.is-resolved .rg-fb-state { background: #e7f6ee; color: #0f7a45; }
.rg-fb-message { margin: 12px 0 0; white-space: pre-wrap; }
.rg-fb-reply {
    margin-top: 12px;
    border-radius: 12px;
    background: #f4f7fb;
    border: 1px solid var(--raniag-border, #dde5ea);
    padding: 10px 12px;
}
.rg-fb-reply-meta { color: #5b6780; font-size: .78rem; font-weight: 700; margin-bottom: 6px; }
.rg-fb-reply-body { max-height: 140px; overflow: auto; font-size: .9rem; }
.rg-fb-reply-body > :last-child { margin-bottom: 0; }
.rg-fb-desk {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: end;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--raniag-border, #dde5ea);
}
.rg-fb-desk form { display: flex; flex-wrap: wrap; gap: 10px 12px; align-items: end; }
.rg-fb-status { border: 0; margin: 0; padding: 0; min-width: 0; }
.rg-fb-status legend { width: 100%; }
.rg-fb-status legend, .rg-fb-note span {
    display: block;
    margin-bottom: 4px;
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #5b6780;
}
.rg-fb-note em { font-style: normal; font-weight: 600; text-transform: none; letter-spacing: 0; color: #8a97a8; }
.rg-fb-status { display: flex; flex-wrap: wrap; gap: 6px; }
.rg-fb-choice { margin: 0; }
.rg-fb-choice input { position: absolute; opacity: 0; }
.rg-fb-choice span {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 .8rem;
    border-radius: 999px;
    border: 1px solid var(--raniag-border, #dde5ea);
    background: #fff;
    font-size: .84rem;
    font-weight: 700;
    cursor: pointer;
}
.rg-fb-choice input:checked + span { background: #0b5ed7; border-color: #0b5ed7; color: #fff; }
.rg-fb-choice input:focus-visible + span { outline: 2px solid #0b5ed7; outline-offset: 2px; }
.rg-fb-note { flex: 1 1 220px; margin: 0; }
.rg-fb-note input {
    width: 100%;
    min-height: 34px;
    border: 1px solid var(--raniag-border, #dde5ea);
    border-radius: 8px;
    padding: .35rem .6rem;
}
.rg-fb-mail { text-align: right; }
.rg-fb-mail p { margin: 0 0 6px; color: #5b6780; font-size: .78rem; }
@media (max-width: 767.98px) {
    .rg-fb-desk { grid-template-columns: 1fr; }
    .rg-fb-mail { text-align: left; }
}
</style>
@endpush

@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js" integrity="sha384-QUJ+ckWz1M+a7w0UfG1sEn4pPrbQwSxGm/1TIPyioqXBrwuT9l4f9gdHWLDLbVWI" crossorigin="anonymous"></script>
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
        bootstrap.Modal.getOrCreateInstance(document.getElementById('replyModal')).show();
    }

    document.getElementById('replyForm').addEventListener('submit', function () {
        document.getElementById('replyHtmlInput').value = getReplyQuill().root.innerHTML;
    });
</script>
@endpush
</x-app-layout>
