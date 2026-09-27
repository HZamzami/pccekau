<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WaitlistEntry;
use App\Policies\Concerns\AuthorizesClinicalRecords;

class WaitlistEntryPolicy
{
    use AuthorizesClinicalRecords;

    // Front-desk manages the wait-list alongside doctors/admins.
    public function create(User $user): bool
    {
        return $user->canManageSchedule();
    }

    public function update(User $user, WaitlistEntry $model): bool
    {
        return $user->canManageSchedule() && $this->sameClinic($user, $model);
    }
}
