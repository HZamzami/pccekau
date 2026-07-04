<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printSummary')
                ->label('Print Summary')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('patients.summary-pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\EditAction::make(),
        ];
    }
}
