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
        @php($trustedDays = (int) config('raniag.two_factor.trusted_device_days', 30))
        @if ($trustedDays > 0)
            <div class="auth-alert auth-alert--info">
                <i class="bi bi-shield-check"></i>
                <div>This device will be remembered for {{ $trustedDays }} days after you verify, so you won't be asked for a code again until then.</div>
            </div>
        @endif
        <button type="submit" class="btn btn-primary w-100">Verify and continue</button>
        <div class="text-center mt-3">
            <a href="{{ route('login') }}">Back to sign in</a>
        </div>
    </form>
</x-auth-split>
