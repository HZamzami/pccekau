<?php

namespace App\Filament\Resources\ImagingReportResource\Pages;

use App\Filament\Resources\ImagingReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImagingReports extends ListRecords
{
    protected static string $resource = ImagingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
