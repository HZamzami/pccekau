<?php

use App\Http\Controllers\ImagingReportPdfController;
use App\Http\Controllers\MdtSummaryPdfController;
use App\Http\Controllers\PatientDocumentDownloadController;
use App\Http\Controllers\PatientSummaryPdfController;
use App\Http\Controllers\StaffScheduleIcsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// Unauthenticated: calendar apps (Google/Apple/Outlook) poll this feed directly
// and can't hold a session; the random ics_token is the access control.
Route::get('staff/{staff}/schedule.ics', StaffScheduleIcsController::class)
    ->name('staff.schedule.ics');

Route::middleware('auth')->group(function () {
    Route::get('imaging-reports/{report}/pdf', ImagingReportPdfController::class)
        ->name('imaging-reports.pdf');

    Route::get('mdt-discussions/{discussion}/pdf', MdtSummaryPdfController::class)
        ->name('mdt-discussions.pdf');

    Route::get('patient-documents/{document}/download/{index}', PatientDocumentDownloadController::class)
        ->name('patient-documents.download');

    Route::get('patients/{patient}/summary-pdf', PatientSummaryPdfController::class)
        ->name('patients.summary-pdf');
});
