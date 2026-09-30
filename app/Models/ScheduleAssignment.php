<?php

namespace App\Models;

use App\Enums\CoverageRole;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleAssignment extends Model
{
    use BelongsToClinic;

    protected $fillable = [
        'oncall_schedule_id',
        'consultant_schedule_id',
        'day',
        'role',
        'staff_id',
    ];

    protected $casts = [
        'day' => 'integer',
        'role' => CoverageRole::class,
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function oncallSchedule(): BelongsTo
    {
        return $this->belongsTo(OncallSchedule::class);
    }

    public function consultantSchedule(): BelongsTo
    {
        return $this->belongsTo(ConsultantSchedule::class);
    }
}
