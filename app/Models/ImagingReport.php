<?php

namespace App\Models;

use App\Enums\ImagingType;
use App\Enums\ProcedureStatus;
use App\Enums\ReportStatus;
use App\Models\Concerns\BelongsToClinic;
use App\Models\Concerns\HasReportWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ImagingReport extends Model
{
    use BelongsToClinic, HasFactory, HasReportWorkflow, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'type',
        'status',
        'date',
        'procedure_status',
        'report',
        'notes',
        'finalized_at',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => ImagingType::class,
        'status' => ReportStatus::class,
        'procedure_status' => ProcedureStatus::class,
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

    public function performers(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'imaging_report_performers');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'imaging_report_readers');
    }

    public function mdtDiscussions(): BelongsToMany
    {
        return $this->belongsToMany(MdtDiscussion::class);
    }

    public function hasSigner(): bool
    {
        return $this->readers()->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
