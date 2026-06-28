<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Staff;

class MdtDiscussion extends Model
{
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
        'contact_number',
    ];

    protected $casts = [
        'discussion_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (MdtDiscussion $discussion) {
            if ($discussion->patient && $discussion->discussion_date) {
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
}
