<?php

namespace App\Filament\Widgets;

use App\Enums\ProcedureCategory;

class WeeklyCathWidget extends WeeklyBookingsWidget
{
    protected static ?int $sort = 4;

    protected function category(): ProcedureCategory
    {
        return ProcedureCategory::Cath;
    }
}
