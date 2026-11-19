<x-app-layout>
    <x-slot name="header">Branding &amp; System Settings</x-slot>

    <main class="container-fluid px-0">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
            <div>
                <span class="text-uppercase small fw-bold text-primary">Administrator controls</span>
                <h1 class="h3 fw-bold mb-1">Branding &amp; System Settings</h1>
                <p class="text-muted mb-0">Manage the identity, colors, public contact details, and display labels shown across RANIAG.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border py-2 px-3"><i class="bi bi-shield-lock me-1"></i>Visible to administrators only</span>
        </div>

        <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>System identity</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="system_name" class="form-label fw-semibold">System name</label>
                            <input id="system_name" name="system_name" class="form-control" maxlength="80" required value="{{ old('system_name', $branding['system_name']) }}">
                            @error('system_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="organization_name" class="form-label fw-semibold">Organization name</label>
                            <input id="organization_name" name="organization_name" class="form-control" maxlength="160" required value="{{ old('organization_name', $branding['organization_name']) }}">
                            @error('organization_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-lg-7">
                            <label for="logo" class="form-label fw-semibold">System logo</label>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light p-2" style="width:4.5rem;height:4.5rem;">
                                    <img src="{{ $branding['logo_url'] }}" alt="Current system logo" class="mw-100 mh-100" style="object-fit:contain;">
                                </span>
                                <div class="flex-grow-1">
                                    <input id="logo" name="logo" type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <div class="form-text">JPG, PNG, or WebP; maximum 2 MB. Uploaded logos are displayed publicly.</div>
                                </div>
                            </div>
                            @error('logo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @if ($setting->logo_path)
                                <label class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1">
                                    <span class="form-check-label">Remove uploaded logo and use the default</span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-palette me-2 text-primary"></i>Brand colors</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Primary and accent colors are used throughout the public and staff interfaces. The page background color applies to light mode; staff and public dark modes remain available.</p>
                    <div class="row g-3">
                        @foreach ([
                            'primary_color' => ['Primary color', $branding['primary_color']],
                            'accent_color' => ['Accent color', $branding['accent_color']],
                            'page_background_color' => ['Light page background', $branding['page_background_color']],
                        ] as $field => [$label, $value])
                            <div class="col-12 col-sm-6 col-lg-4">
                                <label for="{{ $field }}" class="form-label fw-semibold">{{ $label }}</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input id="{{ $field }}" name="{{ $field }}" type="color" class="form-control form-control-color" value="{{ old($field, $value) }}" title="{{ $label }}">
                                    <code>{{ old($field, $value) }}</code>
                                </div>
                                @error($field)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-layout-text-window-reverse me-2 text-primary"></i>Public header and footer</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label for="header_text" class="form-label fw-semibold">Custom header text</label>
                            <input id="header_text" name="header_text" class="form-control" maxlength="180" value="{{ old('header_text', $branding['header_text']) }}" placeholder="Optional line in the public header">
                        </div>
                        <div class="col-12 col-lg-6 d-flex align-items-end">
                            <label class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="show_header_text" value="1" @checked(old('show_header_text', $branding['show_header_text']))>
                                <span class="form-check-label">Show custom header text</span>
                            </label>
                        </div>
                        @error('header_text')<div class="text-danger small">{{ $message }}</div>@enderror
                        <div class="col-12">
                            <label for="footer_text" class="form-label fw-semibold">Custom footer text</label>
                            <textarea id="footer_text" name="footer_text" class="form-control" rows="3" maxlength="500" placeholder="Optional footer message">{{ old('footer_text', $branding['footer_text']) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_footer_text" value="1" @checked(old('show_footer_text', $branding['show_footer_text']))>
                                <span class="form-check-label">Show custom footer text</span>
                            </label>
                        </div>
                        @error('footer_text')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-text mt-3">Navigation and essential footer links stay visible so visitors can continue using the site.</div>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-telephone me-2 text-primary"></i>Organization contact details</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="contact_address" class="form-label fw-semibold">Address</label>
                            <input id="contact_address" name="contact_address" class="form-control" maxlength="255" value="{{ old('contact_address', $branding['contact_address']) }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="contact_phone" class="form-label fw-semibold">Phone</label>
                            <input id="contact_phone" name="contact_phone" type="tel" class="form-control" maxlength="40" value="{{ old('contact_phone', $branding['contact_phone']) }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="contact_email" class="form-label fw-semibold">Email</label>
                            <input id="contact_email" name="contact_email" type="email" class="form-control" maxlength="255" value="{{ old('contact_email', $branding['contact_email']) }}">
                            @error('contact_email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="contact_hotline" class="form-label fw-semibold">Emergency hotline</label>
                            <input id="contact_hotline" name="contact_hotline" type="tel" class="form-control" maxlength="40" value="{{ old('contact_hotline', $branding['contact_hotline']) }}">
                        </div>
                    </div>
                </div>
            </section>

            <section class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Location display labels</h2>
                </div>
                <div class="card-body">
                    <p class="small text-muted">These labels update displayed addresses and headings only. They do not change the municipality boundary, barangay polygons, or jurisdiction rules.</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="municipality_label" class="form-label fw-semibold">Municipality</label>
                            <input id="municipality_label" name="municipality_label" class="form-control" maxlength="120" required value="{{ old('municipality_label', $branding['municipality_label']) }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="province_label" class="form-label fw-semibold">Province</label>
                            <input id="province_label" name="province_label" class="form-control" maxlength="120" required value="{{ old('province_label', $branding['province_label']) }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="response_area_label" class="form-label fw-semibold">Response area / region</label>
                            <input id="response_area_label" name="response_area_label" class="form-control" maxlength="120" required value="{{ old('response_area_label', $branding['response_area_label']) }}">
                        </div>
                    </div>
                </div>
            </section>

            <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>Save branding settings</button>
            </div>
        </form>
    </main>
</x-app-layout>
