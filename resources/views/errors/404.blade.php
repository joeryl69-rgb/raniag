<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Page Not Found — {{ config('raniag.organization') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-icon {
            font-size: 5rem;
            color: #1a6b3a;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 4rem;
            font-weight: 700;
            color: #1a6b3a;
            margin: 0;
            line-height: 1;
        }
        .error-subtitle {
            font-size: 1.5rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 15px;
        }
        .error-message {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 30px;
        }
        .btn-home {
            background: linear-gradient(135deg, #1a6b3a 0%, #0d3d22 100%);
            border: none;
            padding: 12px 24px;
            font-weight: 600;
        }
        .btn-home:hover {
            background: linear-gradient(135deg, #14552f 0%, #0a2e18 100%);
            color: white;
        }
        .error-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>

        <h1 class="error-title">404</h1>
        <h2 class="error-subtitle">Page Not Found</h2>
        <p class="error-message">
            Sorry, the page you are looking for could not be found. It may have been moved, deleted, or you entered the wrong URL.
        </p>

        <div class="d-grid gap-2">
            <a href="{{ route('public.home') }}" class="btn btn-primary btn-home">
                <i class="bi bi-house-door me-2"></i>Back to Home
            </a>

            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
                </a>
            @endauth
        </div>

        <div class="error-footer">
            <p class="mb-1">RANIAG — {{ config('raniag.organization') }}</p>
            <p class="mb-0">Incident Reporting and Analytics System</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
