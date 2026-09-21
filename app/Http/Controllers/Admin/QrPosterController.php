<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class QrPosterController extends Controller
{
    public function index(): View
    {
        $barangays = config('raniag.barangays', []);
        $posters = collect($barangays)->map(function (string $barangay) {
            $url = route('public.report.create', ['barangay' => $barangay], absolute: true);

            return [
                'barangay' => $barangay,
                'url' => $url,
                'qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data='.urlencode($url),
            ];
        });

        return view('admin.qr_posters.index', [
            'posters' => $posters,
        ]);
    }
}
