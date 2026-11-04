<?php

namespace App\Filament\Forms\Components;

use App\Support\PatientDocumentsDisk;
use Filament\Forms\Components\FileUpload;

// The one patient-document uploader. Files show as compact rows (name and
// size) with Open and Download buttons, like an email attachment, instead
// of big inline previews.
class DocumentUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->multiple()
            ->disk(fn () => PatientDocumentsDisk::name())
            ->visibility('private')
            ->directory('patient-documents')
            ->acceptedFileTypes(['application/pdf', 'image/*'])
            ->maxSize(10240)
            ->previewable(false)
            ->openable()
            ->downloadable()
            ->helperText('PDF or images, up to 10 MB each.');
    }
}
