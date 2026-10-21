@props([
    'id',
    'name',
    'label' => 'Reset Password',
    'help' => 'Leave blank to keep the current password.',
])

<div class="mb-3">
    <label for="{{ $id }}" class="form-label">{{ $label }}</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-key"></i></span>
        <input
            type="password"
            class="form-control"
            id="{{ $id }}"
            name="{{ $name }}"
            placeholder="At least 8 characters"
            autocomplete="new-password"
            data-password-strength
            data-password-optional
        >
        <button
            class="btn btn-outline-secondary"
            type="button"
            data-password-toggle
            data-password-label="new password"
            aria-controls="{{ $id }}"
            aria-label="Show new password"
            aria-pressed="false"
        ><i class="bi bi-eye"></i></button>
    </div>
    <div class="form-text">{{ $help }} Use at least 8 characters with lowercase and uppercase letters, a number, and a symbol.</div>
</div>

<div class="mb-0">
    <label for="{{ $id }}_confirmation" class="form-label">Confirm new password</label>
    <div class="input-group">
        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
        <input
            type="password"
            class="form-control"
            id="{{ $id }}_confirmation"
            name="{{ $name }}_confirmation"
            autocomplete="new-password"
            data-password-confirm-of="{{ $id }}"
        >
        <button
            class="btn btn-outline-secondary"
            type="button"
            data-password-toggle
            data-password-label="password confirmation"
            aria-controls="{{ $id }}_confirmation"
            aria-label="Show password confirmation"
            aria-pressed="false"
        ><i class="bi bi-eye"></i></button>
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/password-strength.js') }}?v={{ @filemtime(public_path('js/password-strength.js')) }}"></script>
    @endpush
@endonce
