<?php

namespace App\Models;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProcedureBooking extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'booking_date',
        'slot_type',
        'slot_number',
        'category',
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
        'or' => 'Operating Room',
        'echo' => 'Echo',
    ];

    // Concurrent slots per day, mirroring the original booking sheet. The
    // calendar draws one column per slot, so bookings can't exceed these.
    public static array $slotCapacity = [
        'cath_day_care' => 2,
        'cath_inpatient' => 1,
        'mri_ct' => 1,
        'or' => 1,
        'echo' => 1,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
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
