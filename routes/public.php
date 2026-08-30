<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\IncidentReportController;
use App\Http\Controllers\Public\IncidentTrackController;
use App\Http\Controllers\Public\PublicDashboardController;
use App\Http\Controllers\Public\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::name('public.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::view('/offline', 'public.offline')->name('offline');

    Route::get('/community-dashboard', [PublicDashboardController::class, 'index'])->name('dashboard');
    Route::get('/community-dashboard/data.json', [PublicDashboardController::class, 'data'])->name('dashboard.data');

    Route::post('/feedback', [FeedbackController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('feedback.store');

    Route::prefix('report')->name('report.')->group(function () {
        Route::get('/', [IncidentReportController::class, 'create'])->name('create');
        Route::post('/', [IncidentReportController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('store');
        Route::get('/{trackingNumber}/success', [IncidentReportController::class, 'success'])
            ->name('success');
    });

    Route::get('/track', [IncidentTrackController::class, 'index'])->name('track');
    Route::post('/track', [IncidentTrackController::class, 'show'])
        ->middleware('throttle:20,1')
        ->name('track.lookup');
});
