<section>
    <header class="mb-4">
        <p class="text-muted small mb-0">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <!-- Avatar -->
        <div class="mb-4">
            <label class="form-label fw-semibold text-dark d-block">{{ __('Profile Photo') }}</label>
            <div class="d-flex align-items-center gap-3">
                <div id="avatarPreviewWrap" class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center flex-shrink-0 bg-primary text-white fw-bold" style="width:72px;height:72px;font-size:1.4rem;">
                    @if($user->avatar_url)
                        <img id="avatarPreviewImg" src="{{ $user->avatar_url }}" alt="Profile photo" class="w-100 h-100" style="object-fit:cover;">
                    @else
                        <span id="avatarPreviewInitials">{{ $user->initials }}</span>
                    @endif
                </div>
                <div>
                    <input type="file" name="avatar" id="avatarInput" class="form-control form-control-sm @error('avatar') is-invalid @enderror" accept="image/png,image/jpeg,image/webp" onchange="previewAvatar(this)">
                    <div class="form-text">JPG, PNG or WEBP. Max 4MB.</div>
                    @error('avatar')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    @if($user->avatar_url)
                        <label class="form-check mt-1 small">
                            <input type="checkbox" name="remove_avatar" value="1" class="form-check-input">
                            <span class="form-check-label">Remove current photo</span>
                        </label>
                    @endif
                </div>
            </div>
        </div>

        <!-- Name Field -->
        <div class="mb-3">
            <label for="profile_name" class="form-label fw-semibold text-dark">{{ __('Name') }}</label>
            <input id="profile_name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <!-- Phone Field -->
        <div class="mb-3">
            <label for="profile_phone" class="form-label fw-semibold text-dark">{{ __('Phone Number') }}</label>
            <input id="profile_phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="09XXXXXXXXX" autocomplete="tel">
            <div class="form-text">Used for SMS alerts (new incident submissions, resolution reviews).</div>
            @error('phone')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <!-- Email Field -->
        <div class="mb-3">
            <label for="profile_email" class="form-label fw-semibold text-dark">{{ __('Email Address') }}</label>
            <input id="profile_email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 alert alert-warning p-2 small">
                    <p class="mb-1 text-dark">
                        {{ __('Your email address is unverified.') }}
                    </p>
                    <button form="send-verification" class="btn btn-sm btn-link p-0 align-baseline text-decoration-none">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-success fw-bold mb-0">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary px-4">{{ __('Save Changes') }}</button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Profile updated successfully.') }}</span>
            @endif
        </div>
    </form>
</section>

@once
@push('scripts')
<script>
    function previewAvatar(input) {
        if (!input.files || !input.files[0]) return;
        const wrap = document.getElementById('avatarPreviewWrap');
        const reader = new FileReader();
        reader.onload = e => {
            wrap.innerHTML = `<img src="${e.target.result}" alt="Profile photo" class="w-100 h-100" style="object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
</script>
@endpush
@endonce
