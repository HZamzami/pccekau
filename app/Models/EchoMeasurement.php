<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EchoMeasurement extends Model
{
    protected $fillable = [
        'imaging_report_id',
        'height_cm',
        'weight_kg',
        'bsa',
        'ivsd',
        'lvidd',
        'lvpwd',
        'lvids',
        'la',
        'ao_annulus',
        'ao_root',
        'ef',
        'fs',
        'mv_peak_velocity',
        'mv_peak_gradient',
        'tv_peak_velocity',
        'tv_peak_gradient',
        'pv_peak_velocity',
        'pv_peak_gradient',
        'av_peak_velocity',
        'av_peak_gradient',
    ];

    /** Linear dimensions (cm) that have z-score reference data. */
    public const Z_SCORED = ['ivsd', 'lvidd', 'lvpwd', 'lvids', 'la', 'ao_annulus', 'ao_root'];

    public function imagingReport(): BelongsTo
    {
        return $this->belongsTo(ImagingReport::class);
    }
}
