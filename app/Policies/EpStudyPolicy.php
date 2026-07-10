<?php

namespace App\Policies;

use App\Models\EpStudy;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;
use Illuminate\Database\Eloquent\Model;

class EpStudyPolicy
{
    use AuthorizesClinicalRecords;

    // Finalized studies are locked for everyone; the Amend action is the
    // only way to reopen them.
    public function update(User $user, Model $model): bool
    {
        /** @var EpStudy $model */
        if ($model->isLocked()) {
            return false;
        }

        return $user->canWrite();
    }
}
