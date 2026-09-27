<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;

class PatientPolicy
{
    use AuthorizesClinicalRecords;

    // Front-desk registers patients.
    public function create(User $user): bool
    {
        return $user->canManageSchedule();
    }

    public function update(User $user, Patient $model): bool
    {
        return $user->canManageSchedule() && $this->sameClinic($user, $model);
    }
}
