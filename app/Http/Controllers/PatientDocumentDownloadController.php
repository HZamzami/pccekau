<?php

namespace App\Http\Controllers;

use App\Models\PatientDocument;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class PatientDocumentDownloadController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(PatientDocument $document, int $index)
    {
        $this->authorize('view', $document);

        $path = $document->files[$index] ?? abort(404);

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
