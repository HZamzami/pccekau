<?php

namespace App\Http\Controllers;

use App\Models\MdtDiscussion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MdtSummaryPdfController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(MdtDiscussion $discussion)
    {
        $this->authorize('view', $discussion);

        $discussion->load(['patient', 'specialistFellow', 'imagingReports']);

        return Pdf::loadView('pdf.mdt-summary', [
            'discussion' => $discussion,
            'patient' => $discussion->patient,
        ])->stream("mdt-summary-{$discussion->patient->mrn}-{$discussion->discussion_date->format('Y-m-d')}.pdf");
    }
}
