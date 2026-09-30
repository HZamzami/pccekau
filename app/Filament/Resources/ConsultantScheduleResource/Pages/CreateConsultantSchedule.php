<?php

namespace App\Filament\Resources\ConsultantScheduleResource\Pages;

use App\Filament\Resources\Concerns\SyncsScheduleAssignments;
use App\Filament\Resources\ConsultantScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConsultantSchedule extends CreateRecord
{
    use SyncsScheduleAssignments;

    protected static string $resource = ConsultantScheduleResource::class;
}
