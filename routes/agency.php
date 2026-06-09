<?php

use App\Http\Controllers\Agency\DashboardController;
use App\Http\Controllers\Agency\IncidentController;
use App\Http\Controllers\Agency\ResolutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('agency')
    ->name('agency.')
    ->middleware(['auth', 'verified', 'active', 'role:agency'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard.json', [DashboardController::class, 'api'])->name('dashboard.api');

        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [IncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [IncidentController::class, 'show'])->name('show');
            Route::patch('/{incident}/status', [IncidentController::class, 'updateStatus'])->name('update_status');
            Route::post('/{incident}/accept', [IncidentController::class, 'acceptAssignment'])->name('accept');
        });

        Route::prefix('resolutions')->name('resolutions.')->group(function () {
            Route::post('/{incident}', [ResolutionController::class, 'store'])->name('store');
        });
    });


