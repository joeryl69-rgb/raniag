@props([
    'action',
    'categories',
    'nameValue' => null,
    'emailValue' => null,
    'lockIdentity' => false,
    'orgLabel' => null,
    'backUrl' => null,
])
@if (session('sent'))
    <div class="rg-support-card text-center py-5 mb-4" id="supportSentCard">
        <i class="bi bi-check-circle-fill text-success" style="font-size:2.75rem;"></i>
        <h2 class="h5 fw-bold mt-3 mb-2">Your message has been sent</h2>
        <p class="text-muted mb-1" style="max-width:520px; margin-inline:auto;">
            It's been forwarded to the {{ $orgLabel ?? config('raniag.organization') }} team for review.
            @if($emailValue || !$lockIdentity)
                If you left an email address, our admin will reply to it directly once there's an update.
            @endif
        </p>
        <a href="{{ url()->current() }}" class="btn btn-outline-primary btn-sm mt-3"><i class="bi bi-plus-lg me-1"></i>Send another message</a>
    </div>
@else
<div class="row g-4">
    <div class="col-lg-8">
        <div class="rg-info-box mb-4">
            <i class="bi bi-info-circle-fill"></i>
            <div>All fields marked <span class="text-danger">*</span> are required. Your message is reviewed by the {{ $orgLabel ?? config('raniag.organization') }} team{{ $lockIdentity ? '' : ' — leave your name/email blank to stay anonymous' }}. Responses are sent by email, not through this site.</div>
        </div>

        @if ($errors->any())
            <div class="auth-alert auth-alert--danger mb-4" style="border-radius:12px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $action }}" id="supportForm">
            @csrf

            @if(!$lockIdentity)
                {{-- Honeypot — hidden field real users never fill in. --}}
                <div style="position:absolute; left:-9999px;" aria-hidden="true">
                    <label for="website">Leave this field blank</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>
            @endif

            <div class="rg-support-card mb-4">
                <div class="rg-support-card__head rg-support-card__head--dark">
                    <i class="bi bi-person-fill me-2"></i>Personal Information
                </div>

                @if($lockIdentity)
                    <div class="rg-lock-note">
                        <i class="bi bi-lock-fill"></i>
                        <span>Your name and email are auto-filled from your account and cannot be edited.</span>
                    </div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name @if($lockIdentity)<span class="text-danger">*</span>@endif</label>
                        <input type="text" name="submitter_name" value="{{ old('submitter_name', $nameValue) }}" maxlength="150" class="form-control" placeholder="Your name (optional)" {{ $lockIdentity ? 'readonly' : '' }}>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address @if($lockIdentity)<span class="text-danger">*</span>@endif</label>
                        <input type="email" name="submitter_email" value="{{ old('submitter_email', $emailValue) }}" maxlength="150" class="form-control @error('submitter_email') is-invalid @enderror" placeholder="your.email@example.com" {{ $lockIdentity ? 'readonly' : '' }}>
                        @error('submitter_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if(!$lockIdentity)
                            <div class="form-text">Leave blank to stay anonymous — an admin can only email you back if you provide one.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rg-support-card mb-4">
                <div class="rg-support-card__head">Category <span class="text-danger">*</span></div>
                <input type="hidden" name="category" id="supportCategory" value="{{ old('category') }}" required>
                <div class="rg-cat-grid" id="supportCatGrid">
                    @foreach($categories as $key => $cat)
                        @if(!in_array($key, ['feedback','concern','bug']))
                            <div class="rg-cat-choice" data-value="{{ $key }}" data-label="{{ $cat['label'] }}" role="button" tabindex="0">
                                <i class="bi {{ $cat['icon'] }}"></i>
                                <span class="rg-cat-choice__label">{{ $cat['label'] }}</span>
                                <span class="rg-cat-choice__hint">{{ $cat['hint'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                @error('category')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>

            <div class="rg-support-card mb-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
                    <input type="text" name="subject" id="supportSubject" value="{{ old('subject') }}" maxlength="150" class="form-control @error('subject') is-invalid @enderror" required placeholder="Brief summary of your concern">
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label small fw-semibold">Details <span class="text-danger">*</span></label>
                    <textarea name="message" id="supportMessage" rows="5" maxlength="2000" class="form-control @error('message') is-invalid @enderror" required placeholder="Describe your concern in detail. Include relevant dates, names, or specific issues to help us resolve it faster.">{{ old('message') }}</textarea>
                    <div class="d-flex justify-content-end"><span class="small text-muted" id="supportMsgCount">0 / 2000 characters</span></div>
                    @error('message')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end flex-wrap">
                <a href="{{ $backUrl ?? url()->previous() }}" class="btn btn-outline-secondary px-4"><i class="bi bi-x-lg me-1"></i>Cancel</a>
                <button type="button" id="supportSubmitBtn" class="rg-support-submit"><i class="bi bi-send-fill me-2"></i>Submit</button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="rg-support-card mb-4">
            <div class="rg-support-card__head rg-support-card__head--dark" style="margin:-20px -20px 16px;"><i class="bi bi-graph-up-arrow me-2"></i>What happens next</div>
            <ul class="rg-timeline">
                <li>
                    <span class="rg-timeline__icon rg-timeline__icon--done"><i class="bi bi-send-fill"></i></span>
                    <div><strong>Sent</strong><p>Your message is routed to the {{ $orgLabel ?? config('raniag.organization') }} team.</p></div>
                </li>
                <li>
                    <span class="rg-timeline__icon"><i class="bi bi-search"></i></span>
                    <div><strong>Under Review</strong><p>We review the details and match it to the right unit.</p></div>
                </li>
                <li>
                    <span class="rg-timeline__icon"><i class="bi bi-envelope-fill"></i></span>
                    <div><strong>Reply by Email</strong><p>An admin replies directly to the email you provided.</p></div>
                </li>
                <li>
                    <span class="rg-timeline__icon"><i class="bi bi-check-circle-fill"></i></span>
                    <div><strong>Resolved</strong><p>Issue is resolved and you receive confirmation.</p></div>
                </li>
            </ul>
        </div>

        <div class="rg-support-card mb-4">
            <div class="rg-support-card__head rg-support-card__head--gold" style="margin:-20px -20px 16px;"><i class="bi bi-lightbulb-fill me-2"></i>Quick Tips</div>
            <ul class="rg-tips">
                <li><i class="bi bi-check-circle-fill"></i>Be specific about dates and times</li>
                <li><i class="bi bi-check-circle-fill"></i>Include names of people involved</li>
                <li><i class="bi bi-check-circle-fill"></i>Describe what outcome you expect</li>
                <li><i class="bi bi-check-circle-fill"></i>Leave a valid email if you want a reply</li>
            </ul>
        </div>

        <div class="rg-support-card text-center">
            <i class="bi bi-headset fs-2 text-primary mb-2 d-block"></i>
            <strong class="d-block mb-1">Need urgent help?</strong>
            <p class="small text-muted mb-3">Contact {{ config('raniag.organization') }} directly for emergencies.</p>
            <a href="tel:911" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-telephone-fill me-1"></i>Call Emergency Line</a>
        </div>
    </div>
</div>

{{-- Confirm-before-send dialog. Lightweight, dependency-free overlay so it
     works identically on the public site (Bootstrap-only) and the staff
     portal layout without pulling in a second modal library. --}}
<div class="rg-confirm-backdrop" id="supportConfirmBackdrop" role="dialog" aria-modal="true" aria-labelledby="supportConfirmTitle" hidden>
    <div class="rg-confirm-card">
        <div class="rg-confirm-card__icon"><i class="bi bi-send-fill"></i></div>
        <h3 class="rg-confirm-card__title" id="supportConfirmTitle">Send this message?</h3>
        <p class="rg-confirm-card__body">
            This will be sent to the {{ $orgLabel ?? config('raniag.organization') }} team as
            <span class="fw-semibold" id="supportConfirmCategory">your message</span>.
            @if(!$lockIdentity)
                If you left an email address, that's where any reply will be sent.
            @endif
        </p>
        <div class="rg-confirm-card__actions">
            <button type="button" class="btn btn-outline-secondary px-4" id="supportConfirmCancel">Review Again</button>
            <button type="button" class="rg-support-submit" id="supportConfirmYes"><i class="bi bi-check2 me-2"></i>Yes, Send</button>
        </div>
    </div>
</div>

<style>
    .rg-confirm-backdrop {
        position: fixed; inset: 0; z-index: 1080;
        background: rgba(15, 23, 42, .55);
        display: flex; align-items: center; justify-content: center;
        padding: 16px;
    }
    .rg-confirm-backdrop[hidden] { display: none; }
    .rg-confirm-card {
        background: #fff; border-radius: 16px; padding: 28px 24px;
        max-width: 420px; width: 100%; text-align: center;
        box-shadow: 0 24px 48px -12px rgba(15, 23, 42, .35);
    }
    .rg-confirm-card__icon {
        width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 14px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(11,94,215,.1); color: var(--rg-brand, #0b5ed7); font-size: 1.4rem;
    }
    .rg-confirm-card__title { font-weight: 800; margin-bottom: 8px; }
    .rg-confirm-card__body { color: #5c6b7a; font-size: .92rem; margin-bottom: 20px; }
    .rg-confirm-card__actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
</style>

<script>
(function () {
    const grid = document.getElementById('supportCatGrid');
    const catInput = document.getElementById('supportCategory');
    if (grid) {
        grid.querySelectorAll('.rg-cat-choice').forEach(card => {
            if (card.dataset.value === catInput.value) card.classList.add('is-selected');
            const pick = () => {
                grid.querySelectorAll('.rg-cat-choice').forEach(c => c.classList.remove('is-selected'));
                card.classList.add('is-selected');
                catInput.value = card.dataset.value;
            };
            card.addEventListener('click', pick);
            card.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pick(); } });
        });
    }
    const msg = document.getElementById('supportMessage');
    const count = document.getElementById('supportMsgCount');
    if (msg && count) {
        const update = () => count.textContent = msg.value.length + ' / 2000 characters';
        msg.addEventListener('input', update);
        update();
    }

    // Confirm-before-send: validate the native form first, then show the
    // dialog, and only POST once the user picks "Yes, Send".
    const form = document.getElementById('supportForm');
    const submitBtn = document.getElementById('supportSubmitBtn');
    const backdrop = document.getElementById('supportConfirmBackdrop');
    if (form && submitBtn && backdrop) {
        const catLabel = document.getElementById('supportConfirmCategory');
        const cancelBtn = document.getElementById('supportConfirmCancel');
        const yesBtn = document.getElementById('supportConfirmYes');

        const openDialog = () => {
            const selected = grid ? grid.querySelector('.rg-cat-choice.is-selected') : null;
            catLabel.textContent = selected ? selected.dataset.label : 'your message';
            backdrop.hidden = false;
        };
        const closeDialog = () => { backdrop.hidden = true; };

        submitBtn.addEventListener('click', () => {
            if (!form.reportValidity()) return;
            openDialog();
        });
        cancelBtn.addEventListener('click', closeDialog);
        backdrop.addEventListener('click', (e) => { if (e.target === backdrop) closeDialog(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !backdrop.hidden) closeDialog(); });
        yesBtn.addEventListener('click', () => {
            yesBtn.disabled = true;
            yesBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending…';
            form.submit();
        });
    }
})();
</script>
@endif
