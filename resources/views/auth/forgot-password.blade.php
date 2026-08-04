<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Forgot Password — RANIAG</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root { color-scheme: light; }

        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #eff6ff 0%, #e7f0fd 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-shell { width: min(100%, 460px); }

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

        .login-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0; line-height: 1.3; }
        .login-header p { font-size: 0.9rem; opacity: 0.9; margin: 8px 0 0; }
        .login-body { padding: 28px; }
        .form-label { font-weight: 600; color: #1e2b33; }

        .form-control, .form-control:focus { border-color: #dde5ea; box-shadow: none; }
        .form-control:focus { border-color: #0b5ed7; box-shadow: 0 0 0 0.2rem rgba(11, 94, 215, 0.18); }

        .btn-login {
            background: linear-gradient(135deg, #0b5ed7 0%, #0b5ed7 100%);
            border: none;
            padding: 12px 14px;
            font-weight: 600;
            width: 100%;
            border-radius: 12px;
        }

        .btn-login:hover { background: linear-gradient(135deg, #0b5ed7 0%, #084298 100%); color: white; }

        .login-footer {
            text-align: center;
            padding: 18px 24px 24px;
            background-color: #f4f7ff;
            border-top: 1px solid #dde5ea;
        }

        .login-footer a { color: #0b5ed7; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .login-footer a:hover { text-decoration: underline; }
        .helper-text { color: #5a6b75; font-size: 0.85rem; }

        @media (max-width: 576px) {
            .login-body { padding: 22px 20px 24px; }
            .login-header { padding: 24px 20px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-badge"><i class="bi bi-key"></i></div>
                <h1>RANIAG</h1>
                <p>Account recovery</p>
            </div>

            <div class="login-body">
                <div class="mb-4">
                    <h2 class="h4 fw-semibold mb-1">Forgot your password?</h2>
                    <p class="helper-text mb-0">Enter your registered email and we'll send you a link to choose a new password.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="bi bi-send me-2"></i>Email Password Reset Link
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
