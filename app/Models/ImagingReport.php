<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingReport extends Model
{
    protected $fillable = [
        'patient_id',
        'type',
        'date',
        'report',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public static array $typeLabels = [
        'mri'       => 'MRI',
        'ct'        => 'CT',
        'cath'      => 'Cath',
        'echo_3d'   => '3D Echo',
        'holter'    => 'Holter',
        'stress_ecg'=> 'Stress ECG',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::$typeLabels[$this->type] ?? $this->type;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
