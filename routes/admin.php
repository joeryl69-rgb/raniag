<?php

use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\PersonnelController;
use App\Http\Controllers\Admin\PrintableReportRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResolutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'active', 'role:administrator'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard.json', [DashboardController::class, 'api'])->name('dashboard.api');
        Route::get('/sms-logs', [DashboardController::class, 'smsLogs'])->name('sms-logs');
        Route::get('/audit-logs', [DashboardController::class, 'auditLogs'])->name('audit-logs');

        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [IncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [IncidentController::class, 'show'])->name('show');
            Route::post('/{incident}/validate', [IncidentController::class, 'validate'])->name('validate');
            Route::get('/{incident}/assignments', [IncidentController::class, 'assignments'])->name('assignments');
            Route::put('/{incident}/resolutions/{resolution}', [ResolutionController::class, 'update'])->name('resolutions.update');
        });

        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::post('/', [AssignmentController::class, 'store'])->name('store');
            Route::patch('/{assignment}', [AssignmentController::class, 'update'])->name('update');
            Route::post('/{assignment}/complete', [AssignmentController::class, 'complete'])->name('complete');
        });

        Route::resource('agencies', AgencyController::class)->except(['destroy']);

        Route::prefix('personnel')->name('personnel.')->group(function () {
            Route::get('/{personnel}/edit', [PersonnelController::class, 'edit'])->name('edit');
            Route::put('/{personnel}', [PersonnelController::class, 'update'])->name('update');
        });

        // Printable document requests (admin approve + generate)
        Route::prefix('document-requests')->name('document_requests.')->group(function () {
            Route::get('/', [PrintableReportRequestController::class, 'index'])->name('index');
            Route::post('/{documentRequest}/approve', [PrintableReportRequestController::class, 'approve'])->name('approve');
            Route::post('/{documentRequest}/reject', [PrintableReportRequestController::class, 'reject'])->name('reject');
        });

        Route::prefix('reports')->name('reports.')->group(function () {

            Route::get('/generate', [ReportController::class, 'index'])->name('index');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
        });
    });
