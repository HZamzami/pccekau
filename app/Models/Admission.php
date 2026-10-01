<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Admission extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'admitted_at',
        'ward',
        'bed',
        'admitted_by_id',
        'admission_note',
        'presentation_diagnosis',
        'active_issues',
        'clinical_examination',
        'investigations',
        'medications',
        'oncall_tasks',
        'discharged_at',
        'discharged_by_id',
        'discharge_note',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'admitted_by_id');
    }

    public function dischargedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'discharged_by_id');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(AdmissionProgressNote::class)->orderBy('noted_at');
    }

    // Inpatients currently in the hospital
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('discharged_at');
    }

    /** Length of stay so far (or total once discharged): "2d 5h", "5h", or "<1h". */
    public function stayLabel(): string
    {
        $hours = (int) $this->admitted_at->diffInHours($this->discharged_at ?? now());
        $days = intdiv($hours, 24);
        $hours %= 24;

        return match (true) {
            $days > 0 && $hours > 0 => "{$days}d {$hours}h",
            $days > 0 => "{$days}d",
            $hours > 0 => "{$hours}h",
            default => '<1h',
        };
    }

    public function isActive(): bool
    {
        return $this->discharged_at === null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
