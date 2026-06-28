<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicVisit extends Model
{
    protected $fillable = [
        'patient_id',
        'visit_date',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'next_follow_up_date',
        'seen_by',
    ];

    protected $casts = [
        'visit_date'           => 'date',
        'next_follow_up_date'  => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isOverdue(): bool
    {
        return $this->next_follow_up_date?->isPast() ?? false;
    }

    public function isDueToday(): bool
    {
        return $this->next_follow_up_date?->isToday() ?? false;
    }
}
