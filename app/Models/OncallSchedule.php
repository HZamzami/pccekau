<?php

namespace App\Models;

use App\Enums\CoverageRole;
use App\Models\Concerns\BelongsToClinic;
use App\Models\Concerns\HasDailyAssignments;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OncallSchedule extends Model
{
    use BelongsToClinic, HasDailyAssignments;

    protected $fillable = [
        'week_start',
        'notes',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public static function roles(): array
    {
        return CoverageRole::forCoverage();
    }

    public function getTodayOncallAttribute(): ?string
    {
        return $this->staffForToday(CoverageRole::Oncall)?->name;
    }

    public static function currentWeek(): ?self
    {
        return static::forWeek(Carbon::now()->startOfWeek(Carbon::SUNDAY));
    }

    public static function forWeek(Carbon|string $weekStart): ?self
    {
        $weekStart = $weekStart instanceof Carbon ? $weekStart : Carbon::parse($weekStart);

        return static::with('assignments.staff')
            ->whereDate('week_start', $weekStart->toDateString())
            ->first();
    }
}
