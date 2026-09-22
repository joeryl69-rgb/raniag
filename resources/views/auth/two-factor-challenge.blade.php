<x-auth-split eyebrow="RANIAG PORTAL" title="Verify it's you" subtitle="We emailed a 6-digit code to your account. Enter it below to finish signing in.">
    @if (session('status'))
        <div class="auth-alert auth-alert--info"><i class="bi bi-info-circle-fill"></i><div>{{ session('status') }}</div></div>
    @endif
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
        @php($trustedDays = (int) config('raniag.two_factor.trusted_device_days', -1))
        @if ($trustedDays !== 0)
            <div class="auth-alert auth-alert--info">
                <i class="bi bi-shield-check"></i>
                <div>
                    @if ($trustedDays < 0)
                        This device will be remembered after you verify, so you won't be asked for a code again on it unless you sign out or it's revoked.
                    @else
                        This device will be remembered for {{ $trustedDays }} {{ Str::plural('day', $trustedDays) }} after you verify, so you won't be asked for a code again until then.
                    @endif
                </div>
            </div>
        @endif
        <button type="submit" class="btn btn-primary w-100">Verify and continue</button>
    </form>
    <form method="POST" action="{{ route('two-factor.resend') }}" id="resend-otp-form" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link p-0 small" id="resend-otp-button">Didn't get a code? Resend</button>
    </form>
    <div class="text-center mt-3">
        <a href="{{ route('login') }}">Back to sign in</a>
    </div>
    <x-slot name="scripts">
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('resend-otp-button');
            if (!button) return;
            let remaining = {{ (int) ($resendAvailableInSeconds ?? 0) }};
            function render() {
                if (remaining > 0) {
                    button.disabled = true;
                    button.textContent = `Resend available in ${remaining}s`;
                } else {
                    button.disabled = false;
                    button.textContent = "Didn't get a code? Resend";
                }
            }
            render();
            const timer = setInterval(function () {
                remaining -= 1;
                render();
                if (remaining <= 0) clearInterval(timer);
            }, 1000);
        });
        </script>
    </x-slot>
</x-auth-split>
