<?php

namespace App\Policies;

use App\Models\ClinicVisit;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;

class ClinicVisitPolicy
{
    use AuthorizesClinicalRecords;

    // Nurses record vitals and visit notes.
    public function create(User $user): bool
    {
        return $user->canRecordClinicalNotes();
    }

    public function update(User $user, ClinicVisit $model): bool
    {
        return $user->canRecordClinicalNotes() && $this->sameClinic($user, $model);
    }
}
