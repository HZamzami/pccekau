<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Patient extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'mrn',
        'name',
        'date_of_birth',
        'gender',
        'nationality',
        'blood_type',
        'weight_kg',
        'height_cm',
        'baseline_oxygen_saturation',
        'primary_diagnosis',
        'surgical_history',
        'current_plan',
        'contact_number',
        'referring_physician',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // DB cascadeOnDelete only fires on hard deletes, so soft deletes and
    // restores must cascade to the clinical children at the model layer.
    protected static function booted(): void
    {
        static::deleted(function (Patient $patient) {
            if ($patient->isForceDeleting()) {
                return;
            }

            foreach (['imagingReports', 'clinicVisits', 'mdtDiscussions', 'documents'] as $relation) {
                $patient->{$relation}()->get()->each->delete();
            }
        });

        static::restored(function (Patient $patient) {
            foreach (['imagingReports', 'clinicVisits', 'mdtDiscussions', 'documents'] as $relation) {
                $patient->{$relation}()->onlyTrashed()->get()->each->restore();
            }
        });
    }

    // Returns age as a human-readable string: "5 yr 3 mo" or "8 mo" for infants
    public function getAgeAttribute(): string
    {
        return self::computeAgeLabel($this->date_of_birth, now());
    }

    // Reusable static so MdtDiscussion can call it with a different reference date
    public static function computeAgeLabel(Carbon $dob, Carbon $referenceDate): string
    {
        $years  = (int) $dob->diffInYears($referenceDate);
        $months = (int) $dob->copy()->addYears($years)->diffInMonths($referenceDate);

        if ($years === 0 && $months === 0) {
            $days = (int) $dob->diffInDays($referenceDate);
            return $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        if ($years === 0) {
            return "{$months} mo";
        }

        return $months > 0 ? "{$years} yr {$months} mo" : "{$years} yr";
    }

    public function imagingReports(): HasMany
    {
        return $this->hasMany(ImagingReport::class)->orderByDesc('date');
    }

    public function clinicVisits(): HasMany
    {
        return $this->hasMany(ClinicVisit::class)->orderByDesc('visit_date');
    }

    public function mdtDiscussions(): HasMany
    {
        return $this->hasMany(MdtDiscussion::class)->orderByDesc('discussion_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class)->orderByDesc('created_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
