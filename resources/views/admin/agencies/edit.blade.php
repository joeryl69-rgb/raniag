<x-app-layout>
    <x-slot name="header">
        {{ __('Edit Government Agency') }}
    </x-slot>

    <div class="d-flex mb-4">
        <a href="{{ route('admin.agencies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
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

    <form action="{{ route('admin.agencies.update', $agency->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Agency Info Card -->
            <div class="col-md-6">
                <div class="card raniag-card shadow-sm border-0 h-100">
                    <div class="card-header raniag-card-header bg-white py-3 d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i>Agency Information</h5>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $agency->is_active))>
                            <label class="form-check-label fw-semibold" for="is_active">Active Status</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Agency Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $agency->name) }}" required>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="code" class="form-label">Agency Code/Acronym <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" value="{{ old('code', $agency->code) }}" required>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $agency->phone) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Office Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $agency->email) }}">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Office Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $agency->address) }}">
                        </div>

                        <div class="mb-0">
                            <label for="description" class="form-label">Description / Responsibilities</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $agency->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Officer Login Card -->
            <div class="col-md-6">
                <div class="card raniag-card shadow-sm border-0 h-100">
                    <div class="card-header raniag-card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Officer Login Account</h5>
                    </div>
                    <div class="card-body">
                        @if ($user)
                            <div class="mb-3">
                                <label for="officer_name" class="form-label">Officer's Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="officer_name" name="officer_name" value="{{ old('officer_name', $user->name) }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="officer_email" class="form-label">Officer's Login Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="officer_email" name="officer_email" value="{{ old('officer_email', $user->email) }}" required>
                            </div>

                            <div class="mb-0">
                                <label for="officer_password" class="form-label">Reset Password <span class="text-muted">(leave blank to keep current)</span></label>
                                <input type="text" class="form-control" id="officer_password" name="officer_password" placeholder="Type new password if changing">
                                <div class="form-text">Must be at least 8 characters.</div>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                <p class="mb-0 mt-2 small">No login user is currently mapped to this agency. Re-register or recreate the agency to hook up an officer login.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4 text-end">
                <button type="submit" class="btn btn-primary btn-lg px-4"><i class="bi bi-check-lg me-1"></i>Save Modifications</button>
            </div>
        </div>
    </form>
</x-app-layout>
