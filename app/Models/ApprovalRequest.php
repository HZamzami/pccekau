<?php

namespace App\Models;

use App\Enums\ApprovalProcedure;
use App\Enums\ApprovalStatus;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ApprovalRequest extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'procedure_date',
        'diagnosis',
        'procedure',
        'status',
        'requested_by_id',
    ];

    protected $casts = [
        'procedure_date' => 'date',
        'procedure'      => ApprovalProcedure::class,
        'status'         => ApprovalStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (ApprovalRequest $request) {
            $request->requested_by_id ??= Auth::id();
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
