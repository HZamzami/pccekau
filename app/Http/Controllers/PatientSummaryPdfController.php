<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\ZScoreService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PatientSummaryPdfController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Patient $patient)
    {
        $this->authorize('view', $patient);

        $patient->load([
            'interventions.operator',
            'clinicVisits' => fn ($q) => $q->limit(1),
            'imagingReports' => fn ($q) => $q->with('readers')->limit(5),
        ]);

        $nextFollowUp = $patient->clinicVisits()
            ->whereNotNull('next_follow_up_date')
            ->orderByDesc('next_follow_up_date')
            ->first()
            ?->next_follow_up_date;

        return Pdf::loadView('pdf.patient-summary', [
            'patient' => $patient,
            'bsa' => ZScoreService::bsaHaycock((float) $patient->height_cm, (float) $patient->weight_kg),
            'nextFollowUp' => $nextFollowUp,
        ])->stream("patient-summary-{$patient->mrn}.pdf");
    }
}
