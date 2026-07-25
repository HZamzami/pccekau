<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OncallSchedule extends Model
{
    use BelongsToClinic;

    protected $fillable = [
        'week_start',
        'hijri_date_range',
        'clinic_staff_id',
        'inpatient_staff_id',
        'consultation_staff_id',
        'cath_staff_id',
        'oncall_sunday_id',
        'oncall_monday_id',
        'oncall_tuesday_id',
        'oncall_wednesday_id',
        'oncall_thursday_id',
        'oncall_friday_id',
        'oncall_saturday_id',
        'notes',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public function clinicStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'clinic_staff_id');
    }

    public function inpatientStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'inpatient_staff_id');
    }

    public function consultationStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'consultation_staff_id');
    }

    public function cathStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'cath_staff_id');
    }

    public function oncallSundayStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'oncall_sunday_id');
    }

    public function oncallMondayStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'oncall_monday_id');
    }

    public function oncallTuesdayStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'oncall_tuesday_id');
    }

    public function oncallWednesdayStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'oncall_wednesday_id');
    }

    public function oncallThursdayStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'oncall_thursday_id');
    }

    public function oncallFridayStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'oncall_friday_id');
    }

    public function oncallSaturdayStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'oncall_saturday_id');
    }

    public function getTodayOncallAttribute(): ?string
    {
        $day = ucfirst(strtolower(now()->format('l')));
        $method = "oncall{$day}Staff";

        return $this->$method?->name;
    }

    public static function currentWeek(): ?self
    {
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        return static::with([
            'clinicStaff', 'inpatientStaff', 'consultationStaff', 'cathStaff',
            'oncallSundayStaff', 'oncallMondayStaff', 'oncallTuesdayStaff',
            'oncallWednesdayStaff', 'oncallThursdayStaff', 'oncallFridayStaff',
            'oncallSaturdayStaff',
        ])->where('week_start', $sunday->toDateString())->first();
    }
}
