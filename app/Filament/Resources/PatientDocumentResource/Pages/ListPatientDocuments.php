<?php

namespace App\Filament\Resources\PatientDocumentResource\Pages;

use App\Filament\Resources\PatientDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPatientDocuments extends ListRecords
{
    protected static string $resource = PatientDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
