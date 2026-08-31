<x-auth-split eyebrow="ACCOUNT RECOVERY" :title="'Forgot your password?'" subtitle="Enter your registered email and we'll send you a link to choose a new password.">
    @if (session('status'))
        <div class="auth-alert auth-alert--info"><i class="bi bi-check-circle-fill"></i><div>{{ session('status') }}</div></div>
    @endif
    @if ($errors->any())
        <div class="auth-alert auth-alert--danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-4">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
            </div>
        </div>
        <button type="submit" class="auth-submit"><i class="bi bi-send me-2"></i>Email Password Reset Link</button>
    </form>
    <x-slot name="footer"><a href="{{ route('login') }}"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a></x-slot>
</x-auth-split>
