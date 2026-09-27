<?php

namespace App\Policies;

use App\Models\AdmissionProgressNote;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;

class AdmissionProgressNotePolicy
{
    use AuthorizesClinicalRecords;

    // Nurses add and edit progress notes as part of routine charting.
    public function create(User $user): bool
    {
        return $user->canRecordClinicalNotes();
    }

    public function update(User $user, AdmissionProgressNote $model): bool
    {
        return $user->canRecordClinicalNotes() && $this->sameClinic($user, $model);
    }
}
