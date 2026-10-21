<x-app-layout>
    <x-slot name="header">
        {{ __('Feedback & Concerns') }}
    </x-slot>

<x-workspace-intro
    eyebrow="OVERSIGHT / COMMUNITY SUPPORT"
    title="Feedback & concerns"
    description="Review messages from the public and response teams, then record follow-up or send a reply."
    icon="bi-chat-square-heart"
        variant="feedback-triage"
/>
<p class="small text-muted mb-3">Support Center messages from the public landing page and from signed-in agency/personnel accounts.</p>
<x-kpi-strip :items="[
    ['label' => 'New', 'value' => $counts['new'], 'icon' => 'bi-envelope', 'tone' => 'danger'],
    ['label' => 'Reviewed', 'value' => $counts['reviewed'], 'icon' => 'bi-eye', 'tone' => 'warning'],
    ['label' => 'Resolved', 'value' => $counts['resolved'], 'icon' => 'bi-check-circle', 'tone' => 'success'],
    ['label' => 'In this list', 'value' => $submissions->total(), 'icon' => 'bi-chat-square-text', 'tone' => 'primary', 'sub' => 'Matching the filters'],
]" />

<div class="card rg-filter-host rg-feedback-filter border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <x-filters.toolbar search-placeholder="Search subject, message, name, email" :action="route('admin.feedback.index')" :compact="true">
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
                    <span class="rg-fb-category-icon"><i class="bi {{ $item->categoryIcon() }}" aria-hidden="true"></i></span>
                    <div class="rg-fb-heading">
                        <h2>{{ $item->subject }}</h2>
                        <div class="rg-fb-tags">
                            <span class="rg-fb-tag"><i class="bi bi-tag" aria-hidden="true"></i>{{ $item->categoryLabel() }}</span>
                            <span class="rg-fb-tag">
                                <i class="bi {{ $item->isFromAgency() ? 'bi-building' : 'bi-globe2' }}" aria-hidden="true"></i>
                                @if($item->isFromAgency())
                                    Support Center{{ $item->agency ? ' · '.$item->agency->name : '' }}
                                @else
                                    Public landing page
                                @endif
                            </span>
                            <span class="rg-fb-tag rg-fb-time"><i class="bi bi-clock" aria-hidden="true"></i><time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->diffForHumans() }}</time></span>
                        </div>
                    </div>
                    <span class="rg-fb-state">{{ ucfirst($item->status) }}</span>
                </header>

                <div class="rg-fb-case-body">
                    <section class="rg-fb-message-panel" aria-label="Message from {{ $item->submitter_name ?: 'the sender' }}">
                        <span class="rg-fb-section-label">Message</span>
                        <p class="rg-fb-message">{{ $item->message }}</p>
                    </section>
                    <aside class="rg-fb-contact" aria-label="Sender contact">
                        <span class="rg-fb-section-label">From</span>
                        <strong>{{ $item->submitter_name ?: 'Anonymous sender' }}</strong>
                        @if($item->submitter_email)
                            <a href="mailto:{{ $item->submitter_email }}"><i class="bi bi-envelope" aria-hidden="true"></i>{{ $item->submitter_email }}</a>
                        @else
                            <span class="rg-fb-no-contact">No email provided</span>
                        @endif
                    </aside>
                </div>

                @if($item->admin_reply)
                    <div class="rg-fb-reply">
                        <div class="rg-fb-reply-meta">
                            <i class="bi bi-reply-fill"></i>
                            Email sent {{ $item->replied_at?->diffForHumans() }} by {{ $item->replier?->name ?? '—' }}
                        </div>
                        <div class="rg-fb-reply-body">{{ strip_tags($item->admin_reply) }}</div>
                    </div>
                @endif

                <details class="rg-fb-manage">
                    <summary aria-controls="fb-response-{{ $item->id }}" aria-expanded="false">
                        <span><i class="bi bi-sliders2 me-2" aria-hidden="true"></i>Manage response</span>
                        <small>Change status, add a private note, or email a reply</small>
                        <i class="bi bi-chevron-down rg-fb-manage-chevron" aria-hidden="true"></i>
                    </summary>
                    <div class="rg-fb-manage-panel" id="fb-response-{{ $item->id }}">
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
                        </details>
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
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="replyForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="replyModalTitle"><i class="bi bi-envelope-fill me-2 text-primary"></i>Reply to <span id="replySubject" class="fw-bold ms-1"></span></h5>
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
    --rg-feedback-tone: #b42318;
    background: var(--raniag-card);
    border: 1px solid var(--raniag-border);
    border-top: 3px solid var(--rg-feedback-tone);
    border-radius: .88rem;
    box-shadow: 0 8px 24px color-mix(in srgb, var(--raniag-ink) 5%, transparent);
    padding: 1rem 1.1rem;
}
.rg-fb-case.is-reviewed { --rg-feedback-tone: #a66b0b; }
.rg-fb-case.is-resolved { --rg-feedback-tone: #17734c; }
.rg-fb-head { display: flex; align-items: flex-start; gap: .75rem; }
.rg-fb-category-icon { display: grid; width: 2.35rem; height: 2.35rem; place-items: center; flex: 0 0 auto; border: 1px solid var(--raniag-border); border-radius: .65rem; background: color-mix(in srgb, var(--rg-feedback-tone) 8%, var(--raniag-card)); color: var(--rg-feedback-tone); }
.rg-fb-heading { min-width: 0; }
.rg-fb-head h2 { margin: 0; color: var(--raniag-ink); font-size: 1rem; font-weight: 780; letter-spacing: -.02em; }
.rg-fb-tags { display: flex; flex-wrap: wrap; gap: .3rem .65rem; margin-top: .32rem; }
.rg-fb-tag { display: inline-flex; align-items: center; gap: .3rem; color: var(--raniag-muted); font-size: .69rem; }
.rg-fb-tag .bi { color: var(--raniag-primary); }
.rg-fb-state {
    margin-left: auto;
    border: 1px solid color-mix(in srgb, var(--rg-feedback-tone) 25%, var(--raniag-border));
    border-radius: 999px;
    padding: .25rem .65rem;
    background: color-mix(in srgb, var(--rg-feedback-tone) 8%, var(--raniag-card));
    color: var(--rg-feedback-tone);
    font-size: .68rem;
    font-weight: 800;
}
.rg-fb-case-body { display: grid; grid-template-columns: minmax(0, 1fr) minmax(12rem, 15rem); gap: 1rem; margin-top: .9rem; }
.rg-fb-message-panel { min-width: 0; padding: .8rem .9rem; border-left: 3px solid color-mix(in srgb, var(--raniag-primary) 55%, var(--raniag-border)); border-radius: 0 .62rem .62rem 0; background: color-mix(in srgb, var(--raniag-primary) 4%, var(--raniag-card)); }
.rg-fb-section-label { display: block; margin-bottom: .35rem; color: var(--raniag-muted); font-size: .6rem; font-weight: 850; letter-spacing: .1em; text-transform: uppercase; }
.rg-fb-message { margin: 0; color: var(--raniag-ink); font-size: .85rem; line-height: 1.6; white-space: pre-wrap; overflow-wrap: anywhere; }
.rg-fb-contact { display: flex; flex-direction: column; align-items: flex-start; justify-content: center; gap: .2rem; padding: .65rem .8rem; border: 1px solid var(--raniag-border); border-radius: .62rem; background: var(--raniag-card); }
.rg-fb-contact strong { color: var(--raniag-ink); font-size: .78rem; }
.rg-fb-contact a, .rg-fb-no-contact { display: inline-flex; align-items: center; gap: .35rem; color: var(--raniag-muted); font-size: .69rem; overflow-wrap: anywhere; text-decoration: none; }
.rg-fb-contact a:hover { color: var(--raniag-primary); }
.rg-fb-reply {
    margin-top: .8rem;
    border-radius: .68rem;
    background: color-mix(in srgb, var(--raniag-accent) 5%, var(--raniag-card));
    border: 1px solid var(--raniag-border);
    padding: .7rem .85rem;
}
.rg-fb-reply-meta { color: var(--raniag-muted); font-size: .72rem; font-weight: 700; margin-bottom: .4rem; }
.rg-fb-reply-body { max-height: 140px; overflow: auto; color: var(--raniag-ink); font-size: .82rem; }
.rg-fb-reply-body > :last-child { margin-bottom: 0; }
.rg-fb-desk {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: end;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--raniag-border);
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
    color: var(--raniag-muted);
}
.rg-fb-note em { font-style: normal; font-weight: 600; text-transform: none; letter-spacing: 0; color: var(--raniag-muted); }
.rg-fb-status { display: flex; flex-wrap: wrap; gap: 6px; }
.rg-fb-choice { margin: 0; }
.rg-fb-choice input { position: absolute; opacity: 0; }
.rg-fb-choice span {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 .8rem;
    border-radius: 999px;
    border: 1px solid var(--raniag-border);
    background: var(--raniag-card);
    color: var(--raniag-ink);
    font-size: .84rem;
    font-weight: 700;
    cursor: pointer;
}
.rg-fb-choice input:checked + span { background: var(--raniag-primary); border-color: var(--raniag-primary); color: #fff; }
.rg-fb-choice input:focus-visible + span { outline: 2px solid var(--raniag-primary); outline-offset: 2px; }
.rg-fb-note { flex: 1 1 220px; margin: 0; }
.rg-fb-note input {
    width: 100%;
    min-height: 34px;
    border: 1px solid var(--raniag-border);
    border-radius: 8px;
    padding: .35rem .6rem;
    background: var(--raniag-card);
    color: var(--raniag-ink);
}
.rg-fb-mail { text-align: right; }
.rg-fb-mail p { margin: 0 0 6px; color: var(--raniag-muted); font-size: .72rem; }
@media (max-width: 767.98px) {
    .rg-fb-case-body { grid-template-columns: 1fr; }
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
