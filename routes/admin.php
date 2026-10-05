<?php

use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\HazardEvacController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\IncidentDocumentController;
use App\Http\Controllers\Admin\IncidentTypeController;
use App\Http\Controllers\Admin\PersonnelController;
use App\Http\Controllers\Admin\PersonnelRoleController;
use App\Http\Controllers\Admin\PrintableReportRequestController;
use App\Http\Controllers\Admin\PublicDeskController;
use App\Http\Controllers\Admin\QrPosterController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResolutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'active', 'role:administrator', 'no-cache'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard.json', [DashboardController::class, 'api'])->name('dashboard.api');
        Route::get('/dashboard/boundary.json', [DashboardController::class, 'boundary'])->name('dashboard.boundary');
        Route::get('/dashboard/barangays.json', [DashboardController::class, 'barangays'])->name('dashboard.barangays');
        Route::post('/availability', [\App\Http\Controllers\Shared\AvailabilityController::class, 'toggleSelf'])->name('availability.toggle');

        Route::get('/hazard', [HazardEvacController::class, 'index'])->name('hazard.index');
        Route::post('/hazard/zones', [HazardEvacController::class, 'storeZone'])->name('hazard.zones.store');
        Route::patch('/hazard/zones/{zone}', [HazardEvacController::class, 'updateZone'])->name('hazard.zones.update');
        Route::delete('/hazard/zones/{zone}', [HazardEvacController::class, 'destroyZone'])->name('hazard.zones.destroy');
        Route::post('/hazard/centers', [HazardEvacController::class, 'storeCenter'])->name('hazard.centers.store');
        Route::patch('/hazard/centers/{center}', [HazardEvacController::class, 'updateCenter'])->name('hazard.centers.update');
        Route::delete('/hazard/centers/{center}', [HazardEvacController::class, 'destroyCenter'])->name('hazard.centers.destroy');
        Route::post('/hazard/evacuees', [HazardEvacController::class, 'storeEvacuee'])->name('hazard.evacuees.store');
        Route::patch('/hazard/evacuees/{evacuee}/check-out', [HazardEvacController::class, 'checkOutEvacuee'])->name('hazard.evacuees.checkout');

        Route::get('/qr-posters', [QrPosterController::class, 'index'])->name('qr_posters.index');
        Route::post('/qr-posters', [QrPosterController::class, 'store'])->name('qr_posters.store');
        Route::put('/qr-posters/{qrPoster}', [QrPosterController::class, 'update'])->name('qr_posters.update');
        Route::delete('/qr-posters/{qrPoster}', [QrPosterController::class, 'destroy'])->name('qr_posters.destroy');
        Route::get('/sms-logs', [DashboardController::class, 'smsLogs'])->name('sms-logs');
        Route::get('/audit-logs', [DashboardController::class, 'auditLogs'])->name('audit-logs');

        Route::prefix('feedback')->name('feedback.')->group(function () {
            Route::get('/', [FeedbackController::class, 'index'])->name('index');
            Route::put('/{feedback}', [FeedbackController::class, 'update'])->name('update');
            Route::post('/{feedback}/reply', [FeedbackController::class, 'reply'])->name('reply');
        });

        Route::get('/public-desk', [PublicDeskController::class, 'index'])->name('public_desk.index');
        Route::put('/public-desk/posture', [PublicDeskController::class, 'updatePosture'])->name('public_desk.posture');
        Route::post('/public-desk/hotlines', [PublicDeskController::class, 'storeHotline'])->name('public_desk.hotlines.store');
        Route::put('/public-desk/hotlines/{hotline}', [PublicDeskController::class, 'updateHotline'])->name('public_desk.hotlines.update');
        Route::delete('/public-desk/hotlines/{hotline}', [PublicDeskController::class, 'destroyHotline'])->name('public_desk.hotlines.destroy');

        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::post('/', [AnnouncementController::class, 'store'])->name('store');
            Route::put('/{announcement}', [AnnouncementController::class, 'update'])->name('update');
            Route::post('/{announcement}/toggle', [AnnouncementController::class, 'toggle'])->name('toggle');
            Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])->name('destroy');
        });

        Route::get('/incident-documents', [IncidentDocumentController::class, 'index'])->name('incident_documents.index');
        Route::get('/incident-documents/{incident}', [IncidentDocumentController::class, 'show'])->name('incident_documents.show');

        Route::prefix('incident-types')->name('incident_types.')->group(function () {
            Route::get('/', [IncidentTypeController::class, 'index'])->name('index');
            Route::post('/', [IncidentTypeController::class, 'store'])->name('store');
            Route::put('/{incidentType}', [IncidentTypeController::class, 'update'])->name('update');
            Route::post('/{incidentType}/toggle', [IncidentTypeController::class, 'toggle'])->name('toggle');
            Route::delete('/{incidentType}', [IncidentTypeController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('incidents')->name('incidents.')->group(function () {
            Route::get('/', [IncidentController::class, 'index'])->name('index');
            Route::get('/{incident}', [IncidentController::class, 'show'])->name('show');
            Route::get('/{incident}/live-units', \App\Http\Controllers\Shared\LiveUnitsController::class)->name('live_units');
            Route::post('/{incident}/validate', [IncidentController::class, 'validate'])->name('validate');
            Route::post('/{incident}/reply', [IncidentController::class, 'reply'])->name('reply');
            Route::post('/{incident}/sms-reporter', [\App\Http\Controllers\Shared\ResponderFieldController::class, 'smsReporter'])->name('sms_reporter');
            Route::get('/{incident}/assignments', [IncidentController::class, 'assignments'])->name('assignments');
            Route::put('/{incident}/resolutions/{resolution}', [ResolutionController::class, 'update'])->name('resolutions.update');
            Route::post('/{incident}/documents', [IncidentDocumentController::class, 'store'])->name('documents.store');
            Route::delete('/{incident}/documents/{document}', [IncidentDocumentController::class, 'destroy'])->name('documents.destroy');
            Route::put('/{incident}/documents/{document}/text', [IncidentDocumentController::class, 'updateText'])->name('documents.update_text');
        });

        Route::prefix('assignments')->name('assignments.')->group(function () {
            Route::post('/', [AssignmentController::class, 'store'])->name('store');
            Route::patch('/{assignment}', [AssignmentController::class, 'update'])->name('update');
            Route::post('/{assignment}/complete', [AssignmentController::class, 'complete'])->name('complete');
        });

        Route::resource('agencies', AgencyController::class);

        Route::prefix('personnel')->name('personnel.')->group(function () {
            Route::get('/{personnel}/edit', [PersonnelController::class, 'edit'])->name('edit');
            Route::put('/{personnel}', [PersonnelController::class, 'update'])->name('update');
            Route::delete('/{personnel}', [PersonnelController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('personnel-roles')->name('personnel_roles.')->group(function () {
            Route::get('/', [PersonnelRoleController::class, 'index'])->name('index');
            Route::post('/', [PersonnelRoleController::class, 'store'])->name('store');
            Route::put('/{personnelRole}', [PersonnelRoleController::class, 'update'])->name('update');
            Route::post('/{personnelRole}/toggle', [PersonnelRoleController::class, 'toggle'])->name('toggle');
            Route::delete('/{personnelRole}', [PersonnelRoleController::class, 'destroy'])->name('destroy');
        });

        // Appearance ("System Settings") moved to a per-user preference —
        // see routes/web.php's 'settings.appearance.*' group, available to
        // every authenticated role instead of only administrators.

        // Printable document requests (admin approve + generate)
        Route::prefix('document-requests')->name('document_requests.')->group(function () {
            Route::get('/', [PrintableReportRequestController::class, 'index'])->name('index');
            Route::post('/{documentRequest}/approve', [PrintableReportRequestController::class, 'approve'])->name('approve');
            Route::post('/{documentRequest}/reject', [PrintableReportRequestController::class, 'reject'])->name('reject');
        });

        Route::prefix('reports')->name('reports.')->group(function () {

            Route::get('/generate', [ReportController::class, 'index'])->name('index');
            Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
            Route::post('/decision', [ReportController::class, 'decision'])->name('decision');
            Route::post('/generate-excel', [ReportController::class, 'generateExcel'])->name('generate_excel');
            Route::post('/generate-chart-summary', [ReportController::class, 'chartSummary'])->name('generate_chart_summary');
        });
    });
