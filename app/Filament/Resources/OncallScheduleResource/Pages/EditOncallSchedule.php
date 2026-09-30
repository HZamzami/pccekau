<?php

namespace App\Filament\Resources\OncallScheduleResource\Pages;

use App\Filament\Resources\Concerns\SyncsScheduleAssignments;
use App\Filament\Resources\OncallScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOncallSchedule extends EditRecord
{
    use SyncsScheduleAssignments;

    protected static string $resource = OncallScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
