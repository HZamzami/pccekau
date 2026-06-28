<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $fillable = [
        'name',
        'role',
        'specialty',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static array $specialtyLabels = [
        'ep'       => 'Electrophysiology',
        'cath'     => 'Cath / Interventional',
        'echo'     => 'Echocardiography',
        'icu'      => 'ICU',
        'opd'      => 'OPD / Outpatient',
        'achd'     => 'ACHD',
        'imaging'  => 'Imaging',
        'general'  => 'General',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFellows(Builder $query): Builder
    {
        return $query->where('role', 'fellow');
    }

    public function scopeConsultants(Builder $query): Builder
    {
        return $query->where('role', 'consultant');
    }
}
