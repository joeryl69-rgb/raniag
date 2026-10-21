<x-auth-split eyebrow="ACCOUNT RECOVERY" :title="'Reset your password'" subtitle="Choose a strong new password to secure your account.">
    @if ($errors->any())
        <div class="auth-alert auth-alert--danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <div class="fw-semibold">We couldn't reset your password.</div>
                <ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif
    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            </div>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required autocomplete="new-password" data-password-strength>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle data-password-label="new password" aria-controls="password" aria-label="Show new password" aria-pressed="false"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Re-type new password" required autocomplete="new-password" data-password-confirm-of="password">
                <button class="btn btn-outline-secondary" type="button" data-password-toggle data-password-label="password confirmation" aria-controls="password_confirmation" aria-label="Show password confirmation" aria-pressed="false"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <button type="submit" class="auth-submit" data-submit-text="Reset Password" data-loading-text="Updating password…">
            <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true" data-submit-spinner></span>
            <i class="bi bi-shield-check me-2"></i>
            <span data-submit-label>Reset Password</span>
        </button>
    </form>
    <x-slot name="footer"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a></x-slot>
    <x-slot name="scripts">
        <script src="{{ asset('js/password-strength.js') }}"></script>
    </x-slot>
</x-auth-split>
