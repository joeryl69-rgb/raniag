<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MunicipalityContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MunicipalityContactController extends Controller
{
    public function index(): View
    {
        $contacts = MunicipalityContact::query()
            ->orderBy('municipality')
            ->orderBy('office_name')
            ->get();

        return view('admin.municipality_contacts.index', [
            'contacts' => $contacts,
            'contactMapData' => $contacts->map(fn (MunicipalityContact $contact) => $contact->only([
                'id', 'municipality', 'office_name', 'phone', 'email', 'hotline', 'address', 'latitude', 'longitude', 'is_active',
            ]))->values()->all(),
            'map' => config('raniag.map'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active', true);
        MunicipalityContact::query()->create($data);

        return back()->with('success', 'Referral contact added to the directory.');
    }

    public function update(Request $request, MunicipalityContact $municipalityContact): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $municipalityContact->update($data);

        return back()->with('success', 'Referral contact updated.');
    }

    public function destroy(MunicipalityContact $municipalityContact): RedirectResponse
    {
        $municipalityContact->delete();

        return back()->with('success', 'Referral contact removed. Existing referral records keep their saved details.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'municipality' => ['required', 'string', 'max:120'],
            'office_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+().\\-*#\\s]+$/', 'required_without_all:email,hotline'],
            'email' => ['nullable', 'email', 'max:255', 'required_without_all:phone,hotline'],
            'hotline' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+().\\-*#\\s]+$/', 'required_without_all:phone,email'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', Rule::requiredIf($request->filled('longitude'))],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', Rule::requiredIf($request->filled('latitude'))],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
