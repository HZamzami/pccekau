<?php

namespace App\Policies;

use App\Models\PatientDocument;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;

class PatientDocumentPolicy
{
    use AuthorizesClinicalRecords;

    // Front-desk uploads/manages documents (referrals, consents, etc.).
    public function create(User $user): bool
    {
        return $user->canManageSchedule();
    }

    public function update(User $user, PatientDocument $model): bool
    {
        return $user->canManageSchedule() && $this->sameClinic($user, $model);
    }
}
