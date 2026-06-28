<?php

namespace App\Filament\Resources\ConsultantScheduleResource\Pages;

use App\Filament\Resources\ConsultantScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultantSchedules extends ListRecords
{
    protected static string $resource = ConsultantScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
