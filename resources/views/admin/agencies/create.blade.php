<x-app-layout>
    <x-slot name="header">
        {{ __('Register New Account') }}
    </x-slot>

    <div class="d-flex mb-4">
        <a href="{{ route('admin.agencies.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <h6 class="fw-bold mb-2">Registration failed due to the following errors:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.agencies.store') }}" method="POST">
        @csrf

        <div class="card raniag-card shadow-sm border-0 mb-4">
            <div class="card-body py-3 bg-white">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label class="form-label mb-0">Account Type</label>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group" role="group" aria-label="Account type selection">
                            <input type="radio" class="btn-check" name="account_type" id="account_type_agency" value="agency" autocomplete="off" {{ old('account_type', 'agency') === 'agency' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="account_type_agency">Agency</label>

                            <input type="radio" class="btn-check" name="account_type" id="account_type_personnel" value="personnel" autocomplete="off" {{ old('account_type') === 'personnel' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="account_type_personnel">Personnel</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Agency Info Card -->
            <div id="agency-creation-fields" class="col-md-6 {{ old('account_type', 'agency') !== 'agency' ? 'd-none' : '' }}">
                <div class="card raniag-card shadow-sm border-0 h-100">
                    <div class="card-header raniag-card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-building me-2 text-primary"></i>Agency Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Agency Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Bureau of Fire Protection">
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="code" class="form-label">Agency Code/Acronym <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" value="{{ old('code') }}" required placeholder="e.g. BFP">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Office Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="e.g. bfp@pamplona.gov.ph">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Office Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" placeholder="e.g. Barangay Santa Cruz, Pamplona">
                        </div>

                        <div class="mb-0">
                            <label for="description" class="form-label">Description / Responsibilities</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Explain the responsibilities and scope of this branch..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Officer Login Card -->
            <div class="col-md-6">
                <div class="card raniag-card shadow-sm border-0 h-100">
                    <div class="card-header raniag-card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2 text-primary"></i>Primary Officer Login Account</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="officer_name" class="form-label">Officer's Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="officer_name" name="officer_name" value="{{ old('officer_name') }}" required placeholder="e.g. Chief Inspector John Doe">
                            <div class="form-text">The full display name for the staff officer.</div>
                        </div>
 
                        <div id="personnel-fields" class="{{ old('account_type', 'agency') !== 'personnel' ? 'd-none' : '' }}">
                            <div class="mb-3">
                                <label for="role_title" class="form-label">Personnel Role Title <span class="text-danger">*</span></label>
                                <select class="form-select" id="role_title" name="role_title">
                                    <option value="">Select personnel role</option>
                                    @foreach ($roleTitles as $title)
                                        <option value="{{ $title }}" @selected(old('role_title') === $title)>{{ $title }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">This defines the personnel account role displayed within the response team.</div>
                            </div>

                            <div class="mb-3">
                                <label for="team_assignment" class="form-label">Team Assignment <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="team_assignment" name="team_assignment" value="{{ old('team_assignment') }}" placeholder="e.g. River Search Team">
                                <div class="form-text">Describe the internal team or unit assignment.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. 09123456789">
                        </div>

                        <div class="mb-3">
                            <label for="officer_email" class="form-label">Officer's Login Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="officer_email" name="officer_email" value="{{ old('officer_email') }}" required placeholder="e.g. john.doe@pamplona.gov.ph">
                            <div class="form-text">Used by the officer as their login ID. Must be unique.</div>
                        </div>

                        <div class="mb-0">
                            <label for="officer_password" class="form-label">Temporary Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="officer_password" name="officer_password" required minlength="8">
                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">Generate</button>
                            </div>
                            <div class="form-text">Must be at least 8 characters long. Provide this to the officer for their first login.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4 text-end">
                <button type="submit" class="btn btn-primary btn-lg px-4"><i class="bi bi-check-lg me-1"></i>Save & Create Account</button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            function generatePassword() {
                const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
                let pass = "";
                for (let i = 0; i < 12; i++) {
                    pass += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                document.getElementById('officer_password').value = pass;
            }

            function toggleAccountFields() {
                const accountType = document.querySelector('input[name="account_type"]:checked')?.value;
                const agencyFields = document.getElementById('agency-creation-fields');
                const personnelFields = document.getElementById('personnel-fields');

                if (!agencyFields || !personnelFields) {
                    return;
                }

                const agencyInputs = agencyFields.querySelectorAll('input, textarea, select');
                const personnelInputs = personnelFields.querySelectorAll('input, textarea, select');

                if (accountType === 'personnel') {
                    agencyFields.classList.add('d-none');
                    personnelFields.classList.remove('d-none');

                    agencyInputs.forEach((field) => field.disabled = true);
                    personnelInputs.forEach((field) => field.disabled = false);
                } else {
                    agencyFields.classList.remove('d-none');
                    personnelFields.classList.add('d-none');

                    agencyInputs.forEach((field) => field.disabled = false);
                    personnelInputs.forEach((field) => field.disabled = true);
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                generatePassword();
                toggleAccountFields();

                document.querySelectorAll('input[name="account_type"]').forEach((input) => {
                    input.addEventListener('change', toggleAccountFields);
                });
            });
        </script>
    @endpush
</x-app-layout>
