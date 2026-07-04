<?php

namespace App\Models;

use App\Enums\ImagingType;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ImagingReport extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'type',
        'status',
        'date',
        'report',
        'performed_by_id',
        'notes',
        'signed_by',
        'finalized_at',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => ImagingType::class,
        'status' => ReportStatus::class,
        'finalized_at' => 'datetime',
    ];

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->getLabel() ?? '';
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performed_by_id');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'signed_by');
    }

    public function echoMeasurement(): HasOne
    {
        return $this->hasOne(EchoMeasurement::class);
    }

    public function mdtDiscussions(): BelongsToMany
    {
        return $this->belongsToMany(MdtDiscussion::class);
    }

    public function isLocked(): bool
    {
        return $this->status?->isLocked() ?? false;
    }

    public function isEchoType(): bool
    {
        return $this->type?->isEcho() ?? false;
    }

    public function markPreliminary(): void
    {
        $this->update(['status' => ReportStatus::Preliminary]);
    }

    public function finalize(): void
    {
        $this->update([
            'status' => ReportStatus::Final,
            'finalized_at' => now(),
        ]);
    }

    public function amend(string $reason): void
    {
        // forceFill bypasses the locked-report update policy deliberately:
        // amending is the sanctioned way to reopen a final report.
        $this->forceFill(['status' => ReportStatus::Amended])->save();

        activity()
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('amended');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
