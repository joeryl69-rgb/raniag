<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrPoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QrPosterController extends Controller
{
    public function index(Request $request): View
    {
        $barangays = config('raniag.barangays', []);
        $posters = QrPoster::query()->latest()->get();
        $editing = null;

        if ($request->filled('edit')) {
            $editing = QrPoster::query()->find($request->integer('edit'));
        }

        return view('admin.qr_posters.index', [
            'barangays' => $barangays,
            'posters' => $posters,
            'editing' => $editing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'barangay' => ['required', 'string', Rule::in(config('raniag.barangays', []))],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        QrPoster::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.qr_posters.index')
            ->with('success', 'QR poster created.');
    }

    public function update(Request $request, QrPoster $qrPoster): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'barangay' => ['required', 'string', Rule::in(config('raniag.barangays', []))],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $qrPoster->update([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.qr_posters.index')
            ->with('success', 'QR poster updated.');
    }

    public function destroy(QrPoster $qrPoster): RedirectResponse
    {
        $qrPoster->delete();

        return redirect()
            ->route('admin.qr_posters.index')
            ->with('success', 'QR poster deleted.');
    }
}
