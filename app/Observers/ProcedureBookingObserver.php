<?php

namespace App\Observers;

use App\Models\ProcedureBooking;
use App\Services\ProcedureChartSync;

class ProcedureBookingObserver
{
    public function saved(ProcedureBooking $booking): void
    {
        ProcedureChartSync::fromBooking($booking);
    }

    public function deleted(ProcedureBooking $booking): void
    {
        ProcedureChartSync::bookingDeleted($booking);
    }

    public function restored(ProcedureBooking $booking): void
    {
        ProcedureChartSync::fromBooking($booking);
    }
}
