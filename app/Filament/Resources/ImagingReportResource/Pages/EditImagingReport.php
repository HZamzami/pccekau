<?php

namespace App\Filament\Resources\ImagingReportResource\Pages;

use App\Filament\Resources\ImagingReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImagingReport extends EditRecord
{
    protected static string $resource = ImagingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
