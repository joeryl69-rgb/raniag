<?php

use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IncidentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'active', 'role:administrator'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard.json', [DashboardController::class, 'api'])->name('dashboard.api');

        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [IncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [IncidentController::class, 'show'])->name('show');
            Route::post('/{incident}/validate', [IncidentController::class, 'validate'])->name('validate');
            Route::get('/{incident}/assignments', [IncidentController::class, 'assignments'])->name('assignments');
        });

        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::post('/', [AssignmentController::class, 'store'])->name('store');
            Route::patch('/{assignment}', [AssignmentController::class, 'update'])->name('update');
            Route::post('/{assignment}/complete', [AssignmentController::class, 'complete'])->name('complete');
        });
    });


