<x-auth-split eyebrow="RANIAG PORTAL" title="Two Factor Authentication" subtitle="We emailed a 6-digit code to your account. Enter it below to finish signing in.">
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
        <fieldset class="auth-otp-fieldset mb-3" data-otp-entry>
            <legend class="form-label">Authentication code</legend>
            <div class="auth-otp-boxes" role="group" aria-label="Six-digit authentication code">
                @for ($digit = 1; $digit <= 6; $digit++)
                    <input
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="{{ $digit === 1 ? 6 : 1 }}"
                        class="form-control auth-otp-digit @error('code') is-invalid @enderror"
                        aria-label="Digit {{ $digit }} of 6"
                        aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
                        @if ($digit === 1) autocomplete="one-time-code" autofocus @else autocomplete="off" @endif
                        required
                    >
                @endfor
            </div>
            <input type="hidden" id="code" name="code" value="">
            <p class="auth-otp-hint mb-0">Enter the 6-digit code from your email.</p>
            @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </fieldset>
        @php($trustedDays = (int) config('raniag.two_factor.trusted_device_days', -1))
        @if ($trustedDays !== 0)
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="remember_device" name="remember_device" value="1" checked>
                <label class="form-check-label fw-semibold" for="remember_device">
                    Remember this device
                </label>
            </div>
            <p class="small text-muted mb-3">Uncheck this to remove this account from this browser and require an email code next time.</p>
            <div class="auth-alert auth-alert--info">
                <i class="bi bi-shield-check"></i>
                <div>
                    @if ($trustedDays < 0)
                        This browser will be remembered for this account after verification. After you sign out, tapping that account signs you in without the password or another code, unless the account is removed from this device.
                    @else
                        This browser will be remembered for {{ $trustedDays }} {{ Str::plural('day', $trustedDays) }} after verification, so you won't be asked for a code again when you sign back in until then.
                    @endif
                </div>
            </div>
        @endif
        <button type="submit" class="btn btn-primary auth-submit w-100">Verify and continue</button>
    </form>
    <form method="POST" action="{{ route('two-factor.resend') }}" id="resend-otp-form" class="mt-3">
        @csrf
        <button type="submit" class="btn btn-link p-0 small" id="resend-otp-button">Didn't get a code? Resend</button>
    </form>
    <form method="POST" action="{{ route('two-factor.cancel') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-link p-0">Back to sign in</button>
    </form>
    <x-slot name="scripts">
        <script>
        (function () {
            const entry = document.querySelector('[data-otp-entry]');
            if (!entry) return;

            const form = entry.closest('form');
            const digits = Array.from(entry.querySelectorAll('.auth-otp-digit'));
            const code = entry.querySelector('#code');

            function synchronize() {
                code.value = digits.map((input) => input.value).join('');
            }

            function fillFrom(start, value) {
                const numbers = value.replace(/\D/g, '').slice(0, digits.length - start);
                numbers.split('').forEach((number, offset) => {
                    digits[start + offset].value = number;
                });
                synchronize();
                const next = Math.min(start + numbers.length, digits.length - 1);
                digits[next].focus();
                if (numbers.length && next < digits.length - 1) digits[next].select();
            }

            digits.forEach((input, index) => {
                input.addEventListener('input', () => {
                    const value = input.value.replace(/\D/g, '');
                    if (value.length > 1) {
                        fillFrom(index, value);
                        return;
                    }
                    input.value = value.slice(0, 1);
                    synchronize();
                    if (input.value && index < digits.length - 1) digits[index + 1].focus();
                });

                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Backspace' && !input.value && index > 0) {
                        digits[index - 1].focus();
                    } else if (event.key === 'ArrowLeft' && index > 0) {
                        event.preventDefault();
                        digits[index - 1].focus();
                    } else if (event.key === 'ArrowRight' && index < digits.length - 1) {
                        event.preventDefault();
                        digits[index + 1].focus();
                    }
                });

                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    fillFrom(index, event.clipboardData.getData('text'));
                });
            });

            form.addEventListener('submit', (event) => {
                synchronize();
                if (code.value.length !== digits.length) {
                    event.preventDefault();
                    digits.find((input) => !input.value)?.focus();
                }
            });
        })();

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
