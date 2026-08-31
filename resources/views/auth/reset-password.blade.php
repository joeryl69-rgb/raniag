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
                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required autocomplete="new-password">
                <button class="btn btn-outline-secondary" type="button" id="toggle-password">Show</button>
            </div>
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Re-type new password" required autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="auth-submit"><i class="bi bi-check-circle me-2"></i>Reset Password</button>
    </form>
    <x-slot name="footer"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a></x-slot>
    <x-slot name="scripts">
        <script>
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('toggle-password');
            toggleButton?.addEventListener('click', function () {
                const showing = passwordInput.type === 'text';
                passwordInput.type = showing ? 'password' : 'text';
                toggleButton.textContent = showing ? 'Show' : 'Hide';
            });
        </script>
    </x-slot>
</x-auth-split>
