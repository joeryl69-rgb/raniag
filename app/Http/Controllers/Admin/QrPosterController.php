<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrPosterController extends Controller
{
    public function index(Request $request): View
    {
        $barangays = config('raniag.barangays', []);
        $selected = $request->input('barangays', []);
        if (! is_array($selected)) {
            $selected = [];
        }
        $selected = array_values(array_intersect($barangays, array_map('strval', $selected)));

        $posters = collect($selected)->map(function (string $barangay) {
            $url = route('public.report.create', ['barangay' => $barangay], absolute: true);

            return [
                'barangay' => $barangay,
                'url' => $url,
                'qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data='.urlencode($url),
            ];
        });

        return view('admin.qr_posters.index', [
            'barangays' => $barangays,
            'selected' => $selected,
            'posters' => $posters,
        ]);
    }
}
