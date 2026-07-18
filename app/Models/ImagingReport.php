<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use App\Enums\ImagingType;
use App\Enums\ReportStatus;
use App\Models\Concerns\HasReportWorkflow;
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
    use BelongsToClinic, HasFactory, HasReportWorkflow, LogsActivity, SoftDeletes;

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

    public function isEchoType(): bool
    {
        return $this->type?->isEcho() ?? false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
