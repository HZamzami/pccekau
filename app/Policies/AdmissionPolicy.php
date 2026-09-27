<?php

namespace App\Policies;

use App\Models\Admission;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;

class AdmissionPolicy
{
    use AuthorizesClinicalRecords;

    // Admitting a patient stays a doctor decision (create is unchanged,
    // inherited from the trait); nurses update the handover-board fields
    // on an admission that already exists.
    public function update(User $user, Admission $model): bool
    {
        return $user->canRecordClinicalNotes() && $this->sameClinic($user, $model);
    }
}
