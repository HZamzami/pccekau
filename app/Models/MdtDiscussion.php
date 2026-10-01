<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use App\Observers\ChartProcedureObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(ChartProcedureObserver::class)]
class MdtDiscussion extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'discussion_date',
        'age_snapshot',
        'weight_kg',
        'oxygen_saturation',
        'diagnosis',
        'reason_for_discussion',
        'history',
        'exam_findings',
        'echo_findings',
        'cath_findings',
        'specialist_fellow_id',
        'discussion_results',
        'discussed',
        'contact_number',
    ];

    protected $casts = [
        'discussion_date' => 'date',
        'discussed' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (MdtDiscussion $discussion) {
            if ($discussion->patient?->date_of_birth && $discussion->discussion_date) {
                $discussion->age_snapshot = Patient::computeAgeLabel(
                    $discussion->patient->date_of_birth,
                    $discussion->discussion_date
                );
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function specialistFellow(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'specialist_fellow_id');
    }

    public function imagingReports(): BelongsToMany
    {
        return $this->belongsToMany(ImagingReport::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
