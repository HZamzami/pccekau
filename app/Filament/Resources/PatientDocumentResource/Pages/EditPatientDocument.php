<?php

namespace App\Filament\Resources\PatientDocumentResource\Pages;

use App\Filament\Resources\PatientDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPatientDocument extends EditRecord
{
    protected static string $resource = PatientDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
