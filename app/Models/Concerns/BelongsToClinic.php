<?php

namespace App\Models\Concerns;

use App\Models\Clinic;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

// Row-level tenancy: every clinical record belongs to a clinic. Queries are
// scoped to the current Filament tenant (or the authenticated user's clinic
// on non-panel routes like PDF downloads). Console commands see all rows and
// must iterate clinics explicitly via forClinic().
trait BelongsToClinic
{
    public static function bootBelongsToClinic(): void
    {
        static::addGlobalScope('clinic', function (Builder $query) {
            if ($clinicId = static::currentClinicId()) {
                $query->where($query->getModel()->qualifyColumn('clinic_id'), $clinicId);
            }
        });

        static::creating(function (Model $model) {
            $model->clinic_id ??= static::currentClinicId();
        });
    }

    protected static function currentClinicId(): ?int
    {
        return Filament::getTenant()?->getKey() ?? auth()->user()?->clinic_id;
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeForClinic(Builder $query, Clinic|int $clinic): Builder
    {
        return $query->withoutGlobalScope('clinic')
            ->where($query->getModel()->qualifyColumn('clinic_id'), $clinic instanceof Clinic ? $clinic->getKey() : $clinic);
    }

    public function tapActivity(Activity $activity): void
    {
        $activity->clinic_id = $this->clinic_id;
    }
}
