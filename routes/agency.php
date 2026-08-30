<?php

use App\Http\Controllers\Agency\ArchivedReportController;
use App\Http\Controllers\Agency\DashboardController;
use App\Http\Controllers\Agency\DocumentRequestController;
use App\Http\Controllers\Agency\IncidentController;
use App\Http\Controllers\Agency\ResolutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('agency')
    ->name('agency.')
    ->middleware(['auth', 'verified', 'active', 'role:agency'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard.json', [DashboardController::class, 'api'])->name('dashboard.api');
        Route::get('/dashboard/boundary.json', [DashboardController::class, 'boundary'])->name('dashboard.boundary');
        Route::get('/dashboard/barangays.json', [DashboardController::class, 'barangays'])->name('dashboard.barangays');

        Route::get('/document-requests', [DocumentRequestController::class, 'index'])->name('document_requests.index');
        Route::post('/document-requests/bulk', [DocumentRequestController::class, 'storeBulk'])->name('document_requests.bulk_store');
        Route::patch('/document-requests/{documentRequest}/archive', [DocumentRequestController::class, 'archive'])->name('document_requests.archive');
        Route::patch('/document-requests/{documentRequest}/unarchive', [DocumentRequestController::class, 'unarchive'])->name('document_requests.unarchive');
        Route::post('/incidents/{incident}/print-requests', [DocumentRequestController::class, 'store'])->name('incidents.print_requests.store');

        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [IncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [IncidentController::class, 'show'])->name('show');
            Route::patch('/{incident}/status', [IncidentController::class, 'updateStatus'])->name('update_status');
            Route::post('/{incident}/accept', [IncidentController::class, 'acceptAssignment'])->name('accept');
        });

        Route::post('/incidents/{incident}/resolution', [ResolutionController::class, 'store'])->name('incidents.resolution');
        Route::put('/incidents/{incident}/resolutions/{resolution}', [ResolutionController::class, 'update'])->name('incidents.resolution.update');

        // "Resolved Reports" — read-only history of the agency's resolved/closed
        // incidents (they drop off the active Dispatches list once resolved).
        // The old password-gated ZIP "repository" download was removed: once an
        // incident is resolved its paperwork lives in Document Requests instead.
        Route::prefix('archived-reports')->name('archived_reports.')->group(function () {
            Route::get('/', [ArchivedReportController::class, 'index'])->name('index');
            Route::get('/{incident}', [ArchivedReportController::class, 'show'])->name('show');
        });
    });
