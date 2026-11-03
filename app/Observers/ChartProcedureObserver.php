<?php

namespace App\Observers;

use App\Services\ProcedureChartSync;
use Illuminate\Database\Eloquent\Model;

class ChartProcedureObserver
{
    public function created(Model $record): void
    {
        ProcedureChartSync::createBookingForChartRecord($record);
    }

    public function updated(Model $record): void
    {
        ProcedureChartSync::fromChartRecord($record);
    }
}
