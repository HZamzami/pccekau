<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'specialist'      => 'Specialist',
        'technician'      => 'Technician',
        'resident'        => 'Resident',
        'medical_student' => 'Medical Student',
        'nurse'           => 'Nurse',
    ];

    public static array $specialtyLabels = [
        'ep'               => 'Electrophysiology',
        'cath'             => 'Cardiac Catheterization',
        'advanced_imaging' => 'Advanced Cardiac Imaging',
        'icu'              => 'ICU',
        'achd'             => 'ACHD',
        'general'          => 'General Cardiology',
        'surgery'          => 'Surgery',
    ];

    protected static function booted(): void
    {
        static::creating(function (Staff $staff) {
            $staff->ics_token ??= Str::random(40);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIcsUrlAttribute(): ?string
    {
        return $this->ics_token
            ? route('staff.schedule.ics', ['staff' => $this->id, 'token' => $this->ics_token])
            : null;
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
