<?php

namespace App\Filament\Resources\OncallScheduleResource\Pages;

use App\Filament\Resources\Concerns\SyncsScheduleAssignments;
use App\Filament\Resources\OncallScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOncallSchedule extends CreateRecord
{
    use SyncsScheduleAssignments;

    protected static string $resource = OncallScheduleResource::class;
}
