<?php

namespace App\Http\Controllers;

use App\Models\ImagingReport;
use App\Services\ZScoreService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ImagingReportPdfController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(ImagingReport $report)
    {
        $this->authorize('view', $report);

        $report->load(['patient', 'performedBy', 'signedBy', 'echoMeasurement']);

        $measurement = $report->echoMeasurement;
        $bsa = $measurement
            ? ZScoreService::bsaHaycock((float) $measurement->height_cm, (float) $measurement->weight_kg)
            : null;

        return Pdf::loadView('pdf.imaging-report', [
            'report' => $report,
            'patient' => $report->patient,
            'measurement' => $measurement,
            'bsa' => $bsa,
        ])->stream("imaging-report-{$report->patient->mrn}-{$report->date->format('Y-m-d')}.pdf");
    }
}
