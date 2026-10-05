<x-app-layout>
    <x-slot name="header">{{ __('Public alert and hotlines') }}</x-slot>

    <p class="small text-muted mb-3">These appear on the public home page and the advisories desk. Residents see the alert level in the header on every public page.</p>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Alert posture</h2>
                    <form method="POST" action="{{ route('admin.public_desk.posture') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="alert_level">Level</label>
                            <select name="alert_level" id="alert_level" class="form-select @error('alert_level') is-invalid @enderror">
                                @foreach($postures as $key => $posture)
                                    <option value="{{ $key }}" @selected(old('alert_level', $settings->alert_level ?: 'normal') === $key)>{{ $posture['label'] }}</option>
                                @endforeach
                            </select>
                            @error('alert_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="alert_note">Short public note</label>
                            <textarea name="alert_note" id="alert_note" rows="3" maxlength="280" class="form-control @error('alert_note') is-invalid @enderror" placeholder="Optional. Example: Monitor river levels in low-lying barangays.">{{ old('alert_note', $settings->alert_note) }}</textarea>
                            @error('alert_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save posture</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Add a hotline</h2>
                    <form method="POST" action="{{ route('admin.public_desk.hotlines.store') }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="name">Office</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control" required maxlength="80" placeholder="MDRRMO Operations">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" for="number">Number</label>
                            <input type="text" name="number" id="number" value="{{ old('number') }}" class="form-control" required maxlength="40" placeholder="09xx xxx xxxx">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" for="detail">Note</label>
                            <input type="text" name="detail" id="detail" value="{{ old('detail') }}" class="form-control" maxlength="160" placeholder="24/7">
                        </div>
                        <div class="col-md-2">
                            <input type="hidden" name="is_published" value="1">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="list-group list-group-flush">
                    @forelse($hotlines as $hotline)
                        <div class="list-group-item">
                            <form method="POST" action="{{ route('admin.public_desk.hotlines.update', $hotline) }}" class="row g-2 align-items-center">
                                @csrf
                                @method('PUT')
                                <div class="col-md-3">
                                    <input type="text" name="name" value="{{ $hotline->name }}" class="form-control form-control-sm" required maxlength="80" aria-label="Office">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="number" value="{{ $hotline->number }}" class="form-control form-control-sm" required maxlength="40" aria-label="Number">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="detail" value="{{ $hotline->detail }}" class="form-control form-control-sm" maxlength="160" aria-label="Note">
                                </div>
                                <div class="col-md-1">
                                    <input type="number" name="sort_order" value="{{ $hotline->sort_order }}" class="form-control form-control-sm" min="0" max="999" aria-label="Sort order">
                                </div>
                                <div class="col-md-2 d-flex gap-1 justify-content-end">
                                    <input type="hidden" name="is_published" value="0">
                                    <div class="form-check me-1">
                                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="pub-{{ $hotline->id }}" @checked($hotline->is_published)>
                                        <label class="form-check-label small" for="pub-{{ $hotline->id }}">Live</label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('admin.public_desk.hotlines.destroy', $hotline) }}" class="mt-2" onsubmit="return confirm('Remove this hotline?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small">No hotlines yet. Add the MDRRMO line, police, fire, or barangay contacts residents should call.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
