<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Pages\ImportPatientData;
use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(ImportPatientData::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
