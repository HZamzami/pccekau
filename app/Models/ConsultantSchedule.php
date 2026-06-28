<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ConsultantSchedule extends Model
{
    protected $fillable = [
        'week_start',
        'hijri_date',
        'service_consultant',
        'cath_consultant',
        'ep_consultant',
        'notes',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public static function currentWeek(): ?self
    {
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        return static::where('week_start', $sunday->toDateString())->first();
    }
}
