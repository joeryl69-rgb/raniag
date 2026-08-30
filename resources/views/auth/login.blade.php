<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RANIAG — {{ config('raniag.organization') }} Incident Reporting System</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #eff6ff 0%, #e7f0fd 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-shell {
            width: min(100%, 460px);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.22);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-header {
            background: linear-gradient(135deg, #0b5ed7 0%, #084298 100%);
            color: white;
            padding: 30px 28px 24px;
            text-align: center;
        }

        .brand-badge {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.16);
            margin-bottom: 14px;
            font-size: 1.7rem;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
        }

        .login-header p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 8px 0 0;
        }

        .login-body {
            padding: 28px;
        }

        .form-label {
            font-weight: 600;
            color: #1e2b33;
        }

        .form-control,
        .form-control:focus {
            border-color: #dde5ea;
            box-shadow: none;
        }

        .form-control:focus {
            border-color: #0b5ed7;
            box-shadow: 0 0 0 0.2rem rgba(11, 94, 215, 0.18);
        }

        .btn-login {
            background: linear-gradient(135deg, #0b5ed7 0%, #0b5ed7 100%);
            border: none;
            padding: 12px 14px;
            font-weight: 600;
            width: 100%;
            border-radius: 12px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #0b5ed7 0%, #084298 100%);
            color: white;
        }

        .btn-login:disabled {
            opacity: 0.9;
            cursor: wait;
        }

        .login-footer {
            text-align: center;
            padding: 18px 24px 24px;
            background-color: #f4f7ff;
            border-top: 1px solid #dde5ea;
        }

        .login-footer a {
            color: #0b5ed7;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .helper-text {
            color: #5a6b75;
            font-size: 0.85rem;
        }

        @media (max-width: 576px) {
            .login-body {
                padding: 22px 20px 24px;
            }

            .login-header {
                padding: 24px 20px 20px;
            }
        }
    </style>
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-badge">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h1>RANIAG</h1>
                <p>Secure incident reporting for {{ config('raniag.organization') }}</p>
            </div>

            <div class="login-body">
                <div class="mb-4">
                    <h2 class="h4 fw-semibold mb-1">Sign in to your account</h2>
                    <p class="helper-text mb-0">Use your staff credentials to continue.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-semibold">We couldn't sign you in.</div>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('lockout_seconds'))
                    <div class="alert alert-danger d-flex align-items-center gap-2" id="lockout-banner">
                        <i class="bi bi-shield-lock fs-5"></i>
                        <div>
                            Too many login attempts. Please try again in
                            <strong><span id="lockout-countdown">{{ session('lockout_seconds') }}</span>s</strong>.
                        </div>
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
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="toggle-password">Show password</button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                        <label class="form-check-label" for="remember_me">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login" id="login-submit">
                        <span class="me-2"><i class="bi bi-box-arrow-in-right"></i></span>
                        <span class="button-label">Sign in</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true" id="login-spinner"></span>
                    </button>
                </form>
            </div>

            <div class="login-footer">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">
                        <i class="bi bi-question-circle me-1"></i>Forgot your password?
                    </a>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
                    toggleButton.textContent = showing ? 'Show password' : 'Hide password';
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

            // Lock the form for the remaining cooldown and count down live,
            // so a locked-out account gets an unmistakable "come back later"
            // state instead of just a small inline error under the field.
            let remaining = parseInt(document.getElementById('lockout-countdown')?.textContent || '0', 10);
            if (remaining > 0) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50');
                const countdownEl = document.getElementById('lockout-countdown');
                const timer = setInterval(function () {
                    remaining -= 1;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        document.getElementById('lockout-banner')?.remove();
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-50');
                        return;
                    }
                    if (countdownEl) countdownEl.textContent = remaining;
                }, 1000);
            }
        });
    </script>
</body>
</html>
