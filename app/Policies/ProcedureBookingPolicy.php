<?php

namespace App\Policies;

use App\Models\ProcedureBooking;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;

class ProcedureBookingPolicy
{
    use AuthorizesClinicalRecords;

    // Front-desk manages the procedure calendar alongside doctors/admins.
    public function create(User $user): bool
    {
        return $user->canManageSchedule();
    }

    public function update(User $user, ProcedureBooking $model): bool
    {
        return $user->canManageSchedule() && $this->sameClinic($user, $model);
    }
}
