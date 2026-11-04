<?php

namespace App\Http\Controllers;

use App\Models\PatientDocument;
use App\Support\PatientDocumentsDisk;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class PatientDocumentDownloadController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(PatientDocument $document, int $index)
    {
        $this->authorize('view', $document);

        $path = $document->files[$index] ?? abort(404);

        $disk = Storage::disk(PatientDocumentsDisk::name());

        abort_unless($disk->exists($path), 404);

        return $disk->download($path);
    }
}
