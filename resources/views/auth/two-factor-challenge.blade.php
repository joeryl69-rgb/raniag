<x-auth-split eyebrow="RANIAG PORTAL" title="Verify it's you" subtitle="We emailed a 6-digit code to your account. Enter it below to finish signing in.">
    @if ($errors->any())
        <div class="auth-alert auth-alert--danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        </div>
    @endif
    <form method="POST" action="{{ route('two-factor.store') }}">
        @csrf
        <div class="mb-3">
            <label for="code" class="form-label">Verification code</label>
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="form-control @error('code') is-invalid @enderror" id="code" name="code" required autofocus autocomplete="one-time-code">
            @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="trust_device" name="trust_device">
            <label class="form-check-label" for="trust_device">
                Trust this device for {{ (int) config('raniag.two_factor.trusted_device_days', 30) }} days
            </label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Verify and continue</button>
        <div class="text-center mt-3">
            <a href="{{ route('login') }}">Back to sign in</a>
        </div>
    </form>
</x-auth-split>
