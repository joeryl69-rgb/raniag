<x-app-layout>
    <x-slot name="header">
        {{ __('Profile Settings') }}
    </x-slot>

    <main class="profile-settings-page">
        <section class="profile-settings-hero" aria-labelledby="profile-settings-title">
            <div class="profile-settings-hero-copy">
                <span class="profile-settings-eyebrow"><i class="bi bi-person-circle me-2"></i>ACCOUNT WORKSPACE</span>
                <h1 id="profile-settings-title">Your profile,<br><em>your details.</em></h1>
                <p>Keep your contact information current and your account secure.</p>
            </div>
            <div class="profile-settings-date">
                <i class="bi bi-calendar3"></i>
                <span>{{ now()->format('l, M d, Y') }}</span>
            </div>
            <span class="profile-settings-orbit profile-settings-orbit-one" aria-hidden="true"></span>
            <span class="profile-settings-orbit profile-settings-orbit-two" aria-hidden="true"></span>
        </section>

        <div class="row g-4 profile-settings-grid">
            <section class="col-12 col-xl-7" aria-labelledby="profile-information-title">
                <div class="profile-settings-card h-100">
                    <header class="profile-card-heading">
                        <span class="profile-card-icon"><i class="bi bi-person-vcard"></i></span>
                        <div>
                            <span class="profile-card-kicker">YOUR ACCOUNT</span>
                            <h2 id="profile-information-title">Profile information</h2>
                            <p>Update your name, contact details, and profile photo.</p>
                        </div>
                    </header>
                    <div class="profile-card-body">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </section>

            <section class="col-12 col-xl-5" aria-labelledby="profile-password-title">
                <div class="profile-settings-card h-100">
                    <header class="profile-card-heading">
                        <span class="profile-card-icon profile-card-icon-secure"><i class="bi bi-shield-lock"></i></span>
                        <div>
                            <span class="profile-card-kicker">ACCOUNT SECURITY</span>
                            <h2 id="profile-password-title">Change password</h2>
                            <p>Choose a new password that only you know.</p>
                        </div>
                    </header>
                    <div class="profile-card-body">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </section>

            <section class="col-12" aria-labelledby="profile-delete-title">
                <div class="profile-settings-card profile-danger-card">
                    <div class="profile-danger-copy">
                        <span class="profile-card-icon profile-card-icon-danger"><i class="bi bi-exclamation-triangle"></i></span>
                        <div>
                            <span class="profile-card-kicker">PERMANENT ACTION</span>
                            <h2 id="profile-delete-title">Delete account</h2>
                            <p>Deleting your account permanently removes your profile and associated data. Download anything you need before continuing.</p>
                        </div>
                    </div>
                    <div class="profile-danger-action">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </section>
        </div>
    </main>
<style>
    .profile-settings-page {
        --profile-ink: #163244;
        --profile-muted: #637987;
        --profile-line: #dce5e5;
        --profile-teal: #087b78;
        max-width: 1160px;
        margin: 0 auto;
    }

    .profile-settings-hero {
        position: relative;
        isolation: isolate;
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1.5rem;
        min-height: 230px;
        overflow: hidden;
        margin-bottom: 1.5rem;
        padding: clamp(1.5rem, 4vw, 2.8rem);
        border-radius: 1.25rem;
        background:
            radial-gradient(ellipse at 92% 10%, rgba(112, 218, 190, 0.25), transparent 34%),
            radial-gradient(ellipse at 74% 125%, rgba(235, 194, 111, 0.2), transparent 38%),
            linear-gradient(118deg, #102d3c 0%, #123f4b 58%, #0a625f 100%);
        color: #f1f7f4;
        box-shadow: 0 1.2rem 2.8rem rgba(16, 45, 60, 0.14);
    }

    .profile-settings-hero-copy,
    .profile-settings-date { position: relative; z-index: 1; }
    .profile-settings-eyebrow,
    .profile-card-kicker {
        display: block;
        color: #69d8b8;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.14em;
    }
    .profile-settings-hero h1 {
        margin: 0.8rem 0 0.6rem;
        font-size: clamp(2rem, 4.2vw, 3.4rem);
        font-weight: 750;
        letter-spacing: -0.055em;
        line-height: 0.98;
    }
    .profile-settings-hero h1 em { color: #7fe0c1; font-style: normal; }
    .profile-settings-hero p { max-width: 44ch; margin: 0; color: #c4d8dc; line-height: 1.6; }
    .profile-settings-date {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        flex: 0 0 auto;
        padding: 0.7rem 0.9rem;
        border: 1px solid rgba(226, 246, 240, 0.2);
        border-radius: 99px;
        background: rgba(4, 23, 33, 0.24);
        color: #e2efed;
        font-size: 0.78rem;
        backdrop-filter: blur(10px);
    }
    .profile-settings-orbit {
        position: absolute;
        z-index: -1;
        width: 310px;
        height: 190px;
        border: 1px solid rgba(145, 224, 202, 0.18);
        border-radius: 50%;
        transform: rotate(-32deg);
    }
    .profile-settings-orbit-one { right: 4%; top: -43%; }
    .profile-settings-orbit-two { right: -2%; top: -34%; width: 370px; height: 235px; border-color: rgba(233, 199, 127, 0.2); transform: rotate(24deg); }

    .profile-settings-card {
        overflow: hidden;
        border: 1px solid var(--profile-line);
        border-radius: 1rem;
        background: var(--raniag-card, #fff);
        box-shadow: var(--raniag-card-shadow, 0 0.8rem 2rem rgba(19, 48, 62, 0.06));
    }
    .profile-card-heading {
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        padding: 1.35rem 1.4rem 1.15rem;
        border-bottom: 1px solid var(--raniag-border, var(--profile-line));
    }
    .profile-card-icon {
        display: grid;
        place-items: center;
        width: 2.65rem;
        height: 2.65rem;
        flex: 0 0 2.65rem;
        border-radius: 0.85rem;
        background: rgba(8, 123, 120, 0.1);
        color: var(--profile-teal);
        font-size: 1.15rem;
    }
    .profile-card-icon-secure { background: rgba(11, 94, 215, 0.09); color: var(--raniag-primary, #0b5ed7); }
    .profile-card-heading .profile-card-kicker,
    .profile-danger-copy .profile-card-kicker { margin: 0 0 0.25rem; color: var(--profile-teal); }
    .profile-card-heading h2,
    .profile-danger-copy h2 {
        margin: 0;
        color: var(--raniag-ink, var(--profile-ink));
        font-size: 1.05rem;
        font-weight: 750;
        letter-spacing: -0.02em;
    }
    .profile-card-heading p,
    .profile-danger-copy p {
        margin: 0.3rem 0 0;
        color: var(--raniag-muted, var(--profile-muted));
        font-size: 0.83rem;
        line-height: 1.55;
    }
    .profile-card-body { padding: 1.35rem 1.4rem 1.5rem; }
    .profile-settings-page .form-label {
        margin-bottom: 0.4rem;
        color: var(--raniag-ink, var(--profile-ink)) !important;
        font-size: 0.78rem;
        font-weight: 700 !important;
    }
    .profile-settings-page .form-control,
    .profile-settings-page .input-group-text {
        min-height: 2.85rem;
        border-color: var(--raniag-border, var(--profile-line));
        background-color: var(--raniag-card, #fff);
        color: var(--raniag-ink, var(--profile-ink));
    }
    .profile-settings-page .form-control { border-radius: 0.7rem; }
    .profile-settings-page .input-group > .input-group-text:first-child {
        border-radius: 0.7rem 0 0 0.7rem;
        background-color: var(--raniag-primary-light, #eef5fc);
        color: var(--raniag-primary, #0b5ed7);
    }
    .profile-settings-page .input-group > .form-control:not(:first-child) { border-radius: 0; }
    .profile-settings-page .input-group > .btn:last-child { border-radius: 0 0.7rem 0.7rem 0; }
    .profile-settings-page .form-control:focus {
        border-color: var(--raniag-primary);
        box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--raniag-primary) 15%, transparent);
    }
    .profile-settings-page .form-text { color: var(--raniag-muted, var(--profile-muted)); font-size: 0.74rem; }
    .profile-settings-page .btn-primary {
        min-height: 2.75rem;
        border-radius: 0.7rem;
        font-weight: 700;
    }
    .profile-settings-page .profile-photo-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem;
        border: 1px dashed var(--raniag-border, var(--profile-line));
        border-radius: 0.9rem;
        background: rgba(11, 94, 215, 0.035);
    }
    .profile-settings-page .profile-photo-input { min-width: 0; }
    .profile-danger-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        padding: 1.25rem 1.4rem;
        border-color: rgba(220, 53, 69, 0.25);
    }
    .profile-danger-copy { display: flex; align-items: flex-start; gap: 0.9rem; }
    .profile-card-icon-danger { background: rgba(220, 53, 69, 0.09); color: #c53847; }
    .profile-danger-copy .profile-card-kicker { color: #c53847; }
    .profile-danger-copy p { max-width: 75ch; }
    .profile-danger-action { flex: 0 0 auto; }
    .profile-danger-action .mb-4 { margin-bottom: 0 !important; }
    .profile-danger-action .btn-danger { min-height: 2.65rem; border-radius: 0.7rem; font-weight: 700; }
    [data-theme="dark"] .profile-settings-page {
        --profile-ink: #e8eef6;
        --profile-muted: #b5c5d4;
        --profile-line: var(--raniag-border);
    }
    [data-theme="dark"] .profile-settings-page .profile-photo-row { background: rgba(255,255,255,0.025); }
    @media (max-width: 767.98px) {
        .profile-settings-hero { min-height: 210px; flex-direction: column; align-items: flex-start; }
        .profile-settings-date { align-self: flex-start; }
        .profile-card-heading, .profile-card-body { padding-left: 1rem; padding-right: 1rem; }
        .profile-danger-card { align-items: flex-start; flex-direction: column; }
        .profile-danger-action { align-self: flex-end; }
    }
</style>
</x-app-layout>
