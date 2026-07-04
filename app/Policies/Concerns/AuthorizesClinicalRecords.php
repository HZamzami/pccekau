<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

// Shared shape for clinical records: everyone reads, doctors write, admins delete.
trait AuthorizesClinicalRecords
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, Model $model): bool
    {
        return $user->canWrite();
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $user->isAdmin();
    }
}
