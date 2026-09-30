<?php

namespace App\Filament\Resources\ConsultantScheduleResource\Pages;

use App\Filament\Resources\Concerns\SyncsScheduleAssignments;
use App\Filament\Resources\ConsultantScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsultantSchedule extends EditRecord
{
    use SyncsScheduleAssignments;

    protected static string $resource = ConsultantScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
