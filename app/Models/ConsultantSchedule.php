<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultantSchedule extends Model
{
    use BelongsToClinic;

    protected $fillable = [
        'week_start',
        'service_staff_id',
        'cath_staff_id',
        'ep_staff_id',
        'notes',
    ];

    protected $casts = [
        'week_start' => 'date',
    ];

    public function serviceStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'service_staff_id');
    }

    public function cathStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'cath_staff_id');
    }

    public function epStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'ep_staff_id');
    }

    public static function currentWeek(): ?self
    {
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        return static::where('week_start', $sunday->toDateString())->first();
    }
}
