<x-auth-split eyebrow="RANIAG PORTAL" :title="'Welcome back!'" subtitle="Sign in with your staff credentials to access the incident reporting dashboard.">
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
    <form id="login-form" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required autofocus autocomplete="username">
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" id="toggle-password">Show</button>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                <label class="form-check-label small" for="remember_me">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small fw-semibold text-decoration-none" style="color:#0b5ed7;">Forgot password?</a>
            @endif
        </div>
        <button type="submit" class="auth-submit" id="login-submit">
            <span class="button-label">Sign in</span>
            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" id="login-spinner"></span>
        </button>
    </form>
    <x-slot name="footer">Need an account? Contact your MIS/MDRRMO administrator.</x-slot>
    <x-slot name="scripts">
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('login-form');
            const submitButton = document.getElementById('login-submit');
            const buttonLabel = submitButton?.querySelector('.button-label');
            const spinner = document.getElementById('login-spinner');
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');
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
