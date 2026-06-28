<?php

namespace App\Filament\Resources\OncallScheduleResource\Pages;

use App\Filament\Resources\OncallScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOncallSchedules extends ListRecords
{
    protected static string $resource = OncallScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
