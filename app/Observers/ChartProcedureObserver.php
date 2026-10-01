<?php

namespace App\Observers;

use App\Services\ProcedureChartSync;
use Illuminate\Database\Eloquent\Model;

class ChartProcedureObserver
{
    public function updated(Model $record): void
    {
        ProcedureChartSync::fromChartRecord($record);
    }
}
