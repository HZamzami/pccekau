<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

// clinic_id is intentionally NOT fillable: it is assigned explicitly at
// registration and by Filament's tenant association, never mass-assigned.
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
// Email verification is intentionally not required: users are created by
// their clinic admin with a known password, and registration must work on
// hosts with no mail server configured.
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->clinic ? [$this->clinic] : [];
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant->is($this->clinic);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->clinic;
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isDoctor(): bool
    {
        return $this->role === UserRole::Doctor;
    }

    public function isNurse(): bool
    {
        return $this->role === UserRole::Nurse;
    }

    public function isFrontDesk(): bool
    {
        return $this->role === UserRole::FrontDesk;
    }

    public function canWrite(): bool
    {
        return $this->isAdmin() || $this->isDoctor();
    }

    // Admission progress notes/updates and clinic visit vitals — a level
    // below full clinical write (report finalize/amend, approvals).
    public function canRecordClinicalNotes(): bool
    {
        return $this->canWrite() || $this->isNurse();
    }

    // Patients, documents, procedure bookings, and the wait-list — no
    // clinical note or report access.
    public function canManageSchedule(): bool
    {
        return $this->canWrite() || $this->isFrontDesk();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
