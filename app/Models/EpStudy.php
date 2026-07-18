<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use App\Enums\EpStudyType;
use App\Enums\ReportStatus;
use App\Models\Concerns\HasReportWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class EpStudy extends Model
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
        'type' => EpStudyType::class,
        'status' => ReportStatus::class,
        'finalized_at' => 'datetime',
    ];

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
