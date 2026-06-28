<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FellowsRotation extends Model
{
    protected $fillable = [
        'block_number',
        'start_date',
        'end_date',
        'fellow_name',
        'rotation',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public static array $rotationLabels = [
        'echo'      => 'Echo',
        'ep'        => 'EP',
        'cath'      => 'Cath',
        'opd'       => 'OPD',
        'icu'       => 'ICU',
        'inpatient' => 'Inpatient',
        'research'  => 'Research',
        'achd'      => 'ACHD',
        'imaging'   => 'Imaging',
        'elective'  => 'Elective',
        'vacation'  => 'Vacation',
    ];

    public function getRotationLabelAttribute(): string
    {
        return self::$rotationLabels[$this->rotation] ?? $this->rotation;
    }

    public static function currentBlock(): ?self
    {
        return static::whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->first();
    }
}
