<?php

namespace App\Models;

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
