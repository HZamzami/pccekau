<?php

namespace App\Models;

use App\Enums\CardiacLesion;
use App\Enums\PatientStatus;
use App\Models\Concerns\BelongsToClinic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Patient extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'mrn',
        'phoenix_mrn',
        'name',
        'date_of_birth',
        'gender',
        'nationality',
        'weight_kg',
        'height_cm',
        'primary_diagnosis',
        'lesions',
        'current_plan',
        'contact_number',
        'referring_physician',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        // Plain array cast (not AsEnumCollection): Filament Select multiple
        // works with scalar arrays; use lesionEnums() for labels.
        'lesions' => 'array',
        'status' => PatientStatus::class,
    ];

    /** @return array<CardiacLesion|null> */
    public function lesionEnums(): array
    {
        return array_map(CardiacLesion::tryFrom(...), $this->lesions ?? []);
    }

    // DB cascadeOnDelete only fires on hard deletes, so soft deletes and
    // restores must cascade to the clinical children at the model layer.
    protected static function booted(): void
    {
        static::deleted(function (Patient $patient) {
            if ($patient->isForceDeleting()) {
                return;
            }

            foreach (['imagingReports', 'epStudies', 'clinicVisits', 'admissions', 'approvalRequests', 'mdtDiscussions', 'documents', 'interventions', 'procedureBookings', 'waitlistEntries'] as $relation) {
                $patient->{$relation}()->get()->each->delete();
            }
        });

        static::restored(function (Patient $patient) {
            foreach (['imagingReports', 'epStudies', 'clinicVisits', 'admissions', 'approvalRequests', 'mdtDiscussions', 'documents', 'interventions', 'procedureBookings', 'waitlistEntries'] as $relation) {
                $patient->{$relation}()->onlyTrashed()->get()->each->restore();
            }
        });
    }

    // Returns age as a human-readable string: "5 yr 3 mo" or "8 mo" for infants.
    // Null when date_of_birth isn't known yet (e.g. freshly imported patients).
    public function getAgeAttribute(): ?string
    {
        return $this->date_of_birth ? self::computeAgeLabel($this->date_of_birth, now()) : null;
    }

    // Reusable static so MdtDiscussion can call it with a different reference date
    public static function computeAgeLabel(Carbon $dob, Carbon $referenceDate): string
    {
        $years = (int) $dob->diffInYears($referenceDate);
        $months = (int) $dob->copy()->addYears($years)->diffInMonths($referenceDate);

        if ($years === 0 && $months === 0) {
            $days = (int) $dob->diffInDays($referenceDate);

            return $days.' '.($days === 1 ? 'day' : 'days');
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

    public function epStudies(): HasMany
    {
        return $this->hasMany(EpStudy::class)->orderByDesc('date');
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class)->orderByDesc('created_at');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class)->orderByDesc('admitted_at');
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

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class)->orderByDesc('date');
    }

    public function procedureBookings(): HasMany
    {
        return $this->hasMany(ProcedureBooking::class)->orderByDesc('booking_date');
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class)->orderByDesc('created_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
