<?php

namespace App\Models;

use App\Enums\EpStudyType;
use App\Enums\InterventionType;
use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Models\Concerns\BelongsToClinic;
use App\Observers\ProcedureBookingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(ProcedureBookingObserver::class)]
class ProcedureBooking extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'booking_date',
        'slot_type',
        'slot_number',
        'category',
        'intervention_type',
        'staff_id',
        'procedure',
        'diagnosis',
        'mobile',
        'booked_by',
        'procedure_status',
        'waitlist_entry_id',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'procedure_status' => ProcedureStatus::class,
        'category' => ProcedureCategory::class,
    ];

    public static array $slotTypeLabels = [
        'cath_day_care' => 'Day Care Cath',
        'cath_inpatient' => 'Inpatient Cath',
        'mri_ct' => 'MRI / CT',
        'or' => 'Surgery',
        'echo' => 'Echo',
    ];

    /** The exact procedure ordered: a cath/surgery type, or an invasive EP type for EP. */
    public function exactProcedure(): InterventionType|EpStudyType|null
    {
        return $this->category?->exactProcedure($this->intervention_type);
    }

    // Holds an InterventionType or an EpStudyType depending on the category,
    // so it's stored as a plain string; enums are accepted for convenience.
    public function setInterventionTypeAttribute(\BackedEnum|string|null $value): void
    {
        $this->attributes['intervention_type'] = $value instanceof \BackedEnum ? $value->value : $value;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function imagingReport(): BelongsTo
    {
        return $this->belongsTo(ImagingReport::class);
    }

    public function epStudy(): BelongsTo
    {
        return $this->belongsTo(EpStudy::class);
    }

    public function mdtDiscussion(): BelongsTo
    {
        return $this->belongsTo(MdtDiscussion::class);
    }

    public function waitlistEntry(): BelongsTo
    {
        return $this->belongsTo(WaitlistEntry::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
