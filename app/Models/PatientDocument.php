<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class PatientDocument extends Model
{
    use BelongsToClinic, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'label',
        'category',
        'files',
        'uploaded_by_id',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public static array $categoryLabels = [
        'referral'          => 'Referral',
        'consent'           => 'Consent',
        'lab'               => 'Lab Result',
        'external_report'   => 'External Report',
        'operative_note'    => 'Operative Note',
        'discharge_summary' => 'Discharge Summary',
        'other'             => 'Other',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
