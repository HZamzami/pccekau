<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OncallSchedule extends Model
{
    protected $fillable = [
        'week_start',
        'hijri_date_range',
        'clinic_doctor',
        'inpatient_doctor',
        'consultation_doctor',
        'cath_doctor',
        'service_doctor',
        'ep_doctor',
        'oncall_sunday',
        'oncall_monday',
        'oncall_tuesday',
        'oncall_wednesday',
        'oncall_thursday',
        'oncall_friday',
        'oncall_saturday',
        'notes',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    // Returns today's on-call doctor by matching the current day of week
    public function getTodayOncallAttribute(): ?string
    {
        $day = strtolower(now()->format('l'));

        return $this->{"oncall_{$day}"};
    }

    public static function currentWeek(): ?self
    {
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        return static::where('week_start', $sunday->toDateString())->first();
    }
}
