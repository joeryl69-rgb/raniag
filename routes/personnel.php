<?php

use App\Http\Controllers\Personnel\DashboardController;
use App\Http\Controllers\Personnel\DocumentRequestController;
use App\Http\Controllers\Personnel\IncidentController;
use App\Http\Controllers\Personnel\ResolutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('personnel')
    ->name('personnel.')
    ->middleware(['auth', 'verified', 'active', 'role:personnel'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard.json', [DashboardController::class, 'api'])->name('dashboard.api');

        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [IncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [IncidentController::class, 'show'])->name('show');
            Route::patch('/{incident}/status', [IncidentController::class, 'updateStatus'])->name('update_status');
            Route::post('/{incident}/accept', [IncidentController::class, 'acceptAssignment'])->name('accept');
        });

        Route::post('/incidents/{incident}/print-requests', [DocumentRequestController::class, 'store'])
            ->name('incidents.print_requests.store');

        Route::post('/incidents/{incident}/resolution', [ResolutionController::class, 'store'])->name('incidents.resolution');
        Route::put('/incidents/{incident}/resolutions/{resolution}', [ResolutionController::class, 'update'])->name('incidents.resolution.update');
    });
