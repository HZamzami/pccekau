<?php

namespace App\Policies;

use App\Models\ImagingReport;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalRecords;
use Illuminate\Database\Eloquent\Model;

class ImagingReportPolicy
{
    use AuthorizesClinicalRecords;

    // Finalized reports are locked for everyone; the Amend action is the
    // only way to reopen them.
    public function update(User $user, Model $model): bool
    {
        /** @var ImagingReport $model */
        if ($model->isLocked()) {
            return false;
        }

        return $user->canWrite();
    }
}
