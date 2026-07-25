<?php

namespace App\Http\Controllers;

use App\Models\ImagingReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ImagingReportPdfController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(ImagingReport $report)
    {
        $this->authorize('view', $report);

        $report->load(['patient', 'performers', 'readers']);

        return Pdf::loadView('pdf.imaging-report', [
            'report' => $report,
            'patient' => $report->patient,
        ])->stream("imaging-report-{$report->patient->mrn}-{$report->date->format('Y-m-d')}.pdf");
    }
}
