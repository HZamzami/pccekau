<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ClinicVisit extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'visit_date',
        'weight_kg',
        'height_cm',
        'oxygen_saturation',
        'heart_rate',
        'bp_systolic',
        'bp_diastolic',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'next_follow_up_date',
        'seen_by_id',
    ];

    protected $casts = [
        'visit_date'           => 'date',
        'next_follow_up_date'  => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function seenBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'seen_by_id');
    }

    // Excludes patients who no longer attend follow-up
    public function scopeFollowUpEligible(Builder $query): Builder
    {
        return $query->whereHas('patient', fn (Builder $q) => $q->whereNotIn('status', ['deceased', 'transferred']));
    }

    // Visits whose follow-up is due today or overdue
    public function scopeDueFollowUps(Builder $query): Builder
    {
        return $query->followUpEligible()
            ->whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<=', today());
    }

    public function isOverdue(): bool
    {
        return $this->next_follow_up_date?->isPast() ?? false;
    }

    public function isDueToday(): bool
    {
        return $this->next_follow_up_date?->isToday() ?? false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
