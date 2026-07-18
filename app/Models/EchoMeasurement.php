<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use App\Enums\RegurgGrade;
use App\Enums\RvFunction;
use App\Services\ZScoreService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EchoMeasurement extends Model
{
    use BelongsToClinic, HasFactory;

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
        'tapse',
        'rv_function',
        'mv_regurg',
        'tv_regurg',
        'av_regurg',
        'pv_regurg',
        'mv_mean_gradient',
        'av_mean_gradient',
        'coarct_peak_gradient',
        'coarct_mean_gradient',
        'pda_size_mm',
    ];

    protected $casts = [
        'rv_function' => RvFunction::class,
        'mv_regurg' => RegurgGrade::class,
        'tv_regurg' => RegurgGrade::class,
        'av_regurg' => RegurgGrade::class,
        'pv_regurg' => RegurgGrade::class,
    ];

    /** Linear dimensions (cm) that have z-score reference data. */
    public const Z_SCORED = ['ivsd', 'lvidd', 'lvpwd', 'lvids', 'la', 'ao_annulus', 'ao_root'];

    protected static function booted(): void
    {
        // Created through the imaging report's form, where the tenant context
        // may be absent (e.g. nested Livewire calls) — inherit the parent's.
        static::creating(function (EchoMeasurement $measurement) {
            $measurement->clinic_id ??= $measurement->imagingReport?->clinic_id;
        });

        // The form shows BSA as a computed placeholder; persist it so PDFs
        // and future queries have the value without recomputing.
        static::saving(function (EchoMeasurement $measurement) {
            $measurement->bsa = ZScoreService::bsaHaycock(
                (float) $measurement->height_cm,
                (float) $measurement->weight_kg,
            );
        });

        // Keep the patient's baseline growth data current: a study's
        // height/weight refreshes the chart, but only when no newer
        // echo or weight-bearing clinic visit exists (backfilled old
        // studies must not overwrite fresher data).
        static::saved(function (EchoMeasurement $measurement) {
            if (blank($measurement->height_cm) && blank($measurement->weight_kg)) {
                return;
            }

            $report = $measurement->imagingReport()->with('patient')->first();
            $patient = $report?->patient;

            if ($patient === null) {
                return;
            }

            $newestEchoDate = ImagingReport::where('patient_id', $patient->id)
                ->whereHas('echoMeasurement')
                ->max('date');

            $newestVisitDate = $patient->clinicVisits()
                ->whereNotNull('weight_kg')
                ->max('visit_date');

            if ($newestEchoDate && $report->date->lt(Carbon::parse($newestEchoDate))) {
                return;
            }

            if ($newestVisitDate && $report->date->lt(Carbon::parse($newestVisitDate))) {
                return;
            }

            $patient->update(array_filter([
                'height_cm' => $measurement->height_cm,
                'weight_kg' => $measurement->weight_kg,
            ]));
        });
    }

    public function imagingReport(): BelongsTo
    {
        return $this->belongsTo(ImagingReport::class);
    }
}
