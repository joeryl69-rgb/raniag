<section>
    <header class="mb-4">
        <p class="text-muted small mb-0">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <!-- Current Password -->
        <div class="mb-3">
            <label for="update_password_current_password" class="form-label fw-semibold text-dark">{{ __('Current Password') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input id="update_password_current_password" name="current_password" type="password" class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif" autocomplete="current-password" required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle data-password-label="current password" aria-controls="update_password_current_password" aria-label="Show current password" aria-pressed="false"><i class="bi bi-eye"></i></button>
            </div>
            @if($errors->updatePassword->has('current_password'))
                <div class="invalid-feedback d-block">
                    {{ $errors->updatePassword->first('current_password') }}
                </div>
            @endif
        </div>

        <!-- New Password -->
        <div class="mb-3">
            <label for="update_password_password" class="form-label fw-semibold text-dark">{{ __('New Password') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input id="update_password_password" name="password" type="password" class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif" placeholder="At least 8 characters" autocomplete="new-password" data-password-strength required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle data-password-label="new password" aria-controls="update_password_password" aria-label="Show new password" aria-pressed="false"><i class="bi bi-eye"></i></button>
            </div>
            @if($errors->updatePassword->has('password'))
                <div class="invalid-feedback d-block">
                    {{ $errors->updatePassword->first('password') }}
                </div>
            @endif
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label fw-semibold text-dark">{{ __('Confirm Password') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif" autocomplete="new-password" data-password-confirm-of="update_password_password" required>
                <button class="btn btn-outline-secondary" type="button" data-password-toggle data-password-label="password confirmation" aria-controls="update_password_password_confirmation" aria-label="Show password confirmation" aria-pressed="false"><i class="bi bi-eye"></i></button>
            </div>
            @if($errors->updatePassword->has('password_confirmation'))
                <div class="invalid-feedback d-block">
                    {{ $errors->updatePassword->first('password_confirmation') }}
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary px-4" data-password-submit>{{ __('Update Password') }}</button>

            @if (session('status') === 'password-updated')
                <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Password updated successfully.') }}</span>
            @endif
        </div>
    </form>
</section>

@push('scripts')
    <script src="{{ asset('js/password-strength.js') }}"></script>
@endpush
