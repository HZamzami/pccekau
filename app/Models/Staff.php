<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use BelongsToClinic, HasFactory;

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

    public static array $roleLabels = [
        'consultant'      => 'Consultant',
        'fellow'          => 'Fellow',
        'surgeon'         => 'Surgeon',
        'specialist'      => 'Specialist',
        'technician'      => 'Technician',
        'resident'        => 'Resident',
        'medical_student' => 'Medical Student',
    ];

    public static array $specialtyLabels = [
        'ep'               => 'Electrophysiology',
        'cath'             => 'Cardiac Catheterization',
        'advanced_imaging' => 'Advanced Cardiac Imaging',
        'icu'              => 'ICU',
        'achd'             => 'ACHD',
        'general'          => 'General Cardiology',
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
