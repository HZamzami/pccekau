<?php

namespace App\Models;

use App\Enums\InterventionType;
use App\Enums\ProcedureStatus;
use App\Models\Concerns\BelongsToClinic;
use App\Observers\ChartProcedureObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(ChartProcedureObserver::class)]
class Intervention extends Model
{
    use BelongsToClinic, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'date',
        'procedure_status',
        'type',
        'name',
        'operator_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => InterventionType::class,
        'procedure_status' => ProcedureStatus::class,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
