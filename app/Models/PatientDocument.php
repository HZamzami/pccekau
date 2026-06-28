<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'label',
        'category',
        'files',
        'uploaded_by',
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
}
