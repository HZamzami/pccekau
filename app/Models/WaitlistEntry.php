<?php

namespace App\Models;

use App\Enums\EpStudyType;
use App\Enums\InterventionType;
use App\Enums\ProcedureCategory;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class WaitlistEntry extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'staff_id',
        'category',
        'intervention_type',
        'procedure',
        'diagnosis',
        'priority',
        'mobile',
        'notes',
        'status',
        'booking_id',
        'removed_reason',
    ];

    protected $casts = [
        'category' => ProcedureCategory::class,
        'priority' => WaitlistPriority::class,
        'status' => WaitlistStatus::class,
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ProcedureBooking::class);
    }

    public function getDaysWaitingAttribute(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
