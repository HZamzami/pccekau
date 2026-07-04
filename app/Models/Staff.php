<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'specialty',
        'is_active',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
