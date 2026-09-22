<x-auth-split eyebrow="RANIAG PORTAL" :title="'Sign in to your account'" subtitle="Welcome back. Sign in with your staff credentials to access the incident reporting dashboard.">
    @if (session('status'))
        <div class="auth-alert auth-alert--info"><i class="bi bi-info-circle-fill"></i><div>{{ session('status') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="auth-alert auth-alert--danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <div class="fw-semibold">We couldn't sign you in.</div>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif
    @if (session('lockout_seconds'))
        <div class="auth-alert auth-alert--danger" id="lockout-banner">
            <i class="bi bi-shield-lock-fill"></i>
            <div>Too many login attempts. Please try again in <strong><span id="lockout-countdown">{{ session('lockout_seconds') }}</span>s</strong>.</div>
        </div>
    @endif
    @php($recognizedUsers = $recognizedUsers ?? [])
    @php($hasRecognized = count($recognizedUsers) > 0)
    @php($matchedAccount = collect($recognizedUsers)->first(fn ($a) => strcasecmp($a['email'], (string) old('email')) === 0))
    @php($showSwitcher = $hasRecognized && ! old('email'))
    @php($lockEmail = (bool) $matchedAccount)

    @if ($hasRecognized)
        {{-- Facebook/Google-style account switcher: every account that has
             signed in on this browser before, one tap to continue. --}}
        <div class="rg-account-switcher mb-3 @if (! $showSwitcher) d-none @endif" id="account-switcher">
            <div class="rg-account-switcher-title">Choose an account</div>
            <div class="rg-account-list">
                @foreach ($recognizedUsers as $account)
                    <div class="rg-account-item" data-email="{{ $account['email'] }}" role="button" tabindex="0">
                        <div class="rg-account-avatar">{{ Str::upper(Str::substr($account['name'], 0, 1)) }}</div>
                        <div class="rg-account-info">
                            <div class="rg-account-name">{{ $account['name'] }}</div>
                            <div class="rg-account-email">{{ $account['email'] }}</div>
                        </div>
                        <form method="POST" action="{{ route('login.forget-device') }}" class="rg-account-remove-form">
                            @csrf
                            <input type="hidden" name="email" value="{{ $account['email'] }}">
                            <button type="submit" class="rg-account-remove" title="Remove {{ $account['name'] }}" aria-label="Remove {{ $account['name'] }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
                <button type="button" class="rg-account-item rg-account-add" id="rg-add-account">
                    <div class="rg-account-avatar rg-account-avatar-add"><i class="bi bi-plus-lg"></i></div>
                    <div class="rg-account-info">
                        <div class="rg-account-name">Add another account</div>
                    </div>
                </button>
            </div>
        </div>
    @endif
    <form id="login-form" method="POST" action="{{ route('login') }}" class="@if ($showSwitcher) d-none @endif">
        @csrf
        @if ($hasRecognized)
            <button type="button" class="btn btn-link btn-sm p-0 mb-3" id="rg-switch-account">
                <i class="bi bi-arrow-left"></i> Choose a different account
            </button>
        @endif
        <div class="mb-3 @if ($lockEmail) d-none @endif" id="email-field-group">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $matchedAccount['email'] ?? '') }}" placeholder="name@example.com" required @if (! $lockEmail) autofocus @endif autocomplete="username">
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required @if ($lockEmail) autofocus @endif autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" id="toggle-password">Show password</button>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex align-items-center justify-content-end mb-4">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small fw-semibold text-decoration-none" style="color:#0b5ed7;">Forgot password?</a>
            @endif
        </div>
        <button type="submit" class="auth-submit" id="login-submit">
            <span class="button-label">Sign in</span>
            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" id="login-spinner"></span>
        </button>
    </form>
    <x-slot name="footer">Need an account? Contact your MDRRMO administrator.</x-slot>
    <x-slot name="scripts">
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('login-form');
            const submitButton = document.getElementById('login-submit');
            const buttonLabel = submitButton?.querySelector('.button-label');
            const spinner = document.getElementById('login-spinner');
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');
            const emailInput = document.getElementById('email');
            const emailGroup = document.getElementById('email-field-group');
            const switcher = document.getElementById('account-switcher');
            const switchAccountButton = document.getElementById('rg-switch-account');
            const addAccountButton = document.getElementById('rg-add-account');

            function chooseAccount(email) {
                if (emailInput) emailInput.value = email || '';
                if (emailGroup) emailGroup.classList.toggle('d-none', !!email);
                switcher?.classList.add('d-none');
                form?.classList.remove('d-none');
                (email ? passwordInput : emailInput)?.focus();
            }

            switcher?.querySelectorAll('.rg-account-item[data-email]').forEach(function (item) {
                item.addEventListener('click', function () {
                    chooseAccount(item.dataset.email);
                });
                item.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        chooseAccount(item.dataset.email);
                    }
                });
                item.querySelector('.rg-account-remove-form')?.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            });
            addAccountButton?.addEventListener('click', function () {
                chooseAccount('');
            });
            switchAccountButton?.addEventListener('click', function () {
                form?.classList.add('d-none');
                switcher?.classList.remove('d-none');
            });
            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function () {
                    const showing = passwordInput.type === 'text';
                    passwordInput.type = showing ? 'password' : 'text';
                    toggleButton.textContent = showing ? 'Show' : 'Hide';
                    toggleButton.setAttribute('aria-pressed', showing ? 'false' : 'true');
                });
            }
            if (form && submitButton && buttonLabel && spinner) {
                form.addEventListener('submit', function () {
                    submitButton.disabled = true;
                    buttonLabel.textContent = 'Signing in...';
                    spinner.classList.remove('d-none');
                });
            }
            let remaining = parseInt(document.getElementById('lockout-countdown')?.textContent || '0', 10);
            if (remaining > 0) {
                submitButton.disabled = true;
                submitButton.style.opacity = 0.6;
                const countdownEl = document.getElementById('lockout-countdown');
                const timer = setInterval(function () {
                    remaining -= 1;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        document.getElementById('lockout-banner')?.remove();
                        submitButton.disabled = false;
                        submitButton.style.opacity = 1;
                        return;
                    }
                    if (countdownEl) countdownEl.textContent = remaining;
                }, 1000);
            }
        });
        </script>
    </x-slot>
</x-auth-split>
