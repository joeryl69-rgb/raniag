<x-app-layout>
    <x-slot name="header">
        {{ __('Edit Personnel Account') }}
    </x-slot>

    <div class="d-flex mb-4">
        <a href="{{ route('admin.agencies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Accounts
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <h6 class="fw-bold mb-2">Update failed due to the following errors:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.personnel.update', $personnel->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card raniag-card shadow-sm border-0 h-100">
                    <div class="card-header raniag-card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Personnel Details</h5>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $personnel->is_active))>
                            <label class="form-check-label fw-semibold" for="is_active">Active Status</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $personnel->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Login Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $personnel->email) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $personnel->phone) }}">
                        </div>

                        <div class="mb-3">
                            <label for="role_title" class="form-label">Personnel Role Title <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_title" name="role_title" required>
                                <option value="">Select role title</option>
                                @foreach ($roleTitles as $title)
                                    <option value="{{ $title }}" @selected(old('role_title', $personnel->role_title) === $title)>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="team_assignment" class="form-label">Team Assignment <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="team_assignment" name="team_assignment" value="{{ old('team_assignment', $personnel->team_assignment) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card raniag-card shadow-sm border-0 h-100">
                    <div class="card-header raniag-card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-lock-fill me-2 text-primary"></i>Account Security</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="password" class="form-label">Reset Password</label>
                            <input type="text" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                            <div class="form-text">Enter a new password only if you want to reset this login.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary btn-lg px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
            </div>
        </div>
    </form>
</x-app-layout>
