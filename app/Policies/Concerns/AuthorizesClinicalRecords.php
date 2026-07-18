<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

// Shared shape for clinical records: everyone reads, doctors write, admins
// delete — always within their own clinic. The same-clinic check is defense
// in depth on top of the BelongsToClinic global scope; it is what protects
// the non-panel routes (PDF downloads) if a record ever escapes the scope.
trait AuthorizesClinicalRecords
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->sameClinic($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, Model $model): bool
    {
        return $user->canWrite() && $this->sameClinic($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin() && $this->sameClinic($user, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->isAdmin() && $this->sameClinic($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->isAdmin() && $this->sameClinic($user, $model);
    }

    protected function sameClinic(User $user, Model $model): bool
    {
        return $user->clinic_id !== null && $user->clinic_id === $model->clinic_id;
    }
}
