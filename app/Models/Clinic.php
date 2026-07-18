<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'created_by',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // Filament's tenancy associates records created through resource pages
    // via these relationships (camel-plural of the model name) — every
    // tenant model with a Filament resource needs one here.
    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function clinicVisits(): HasMany
    {
        return $this->hasMany(ClinicVisit::class);
    }

    public function imagingReports(): HasMany
    {
        return $this->hasMany(ImagingReport::class);
    }

    public function epStudies(): HasMany
    {
        return $this->hasMany(EpStudy::class);
    }

    public function mdtDiscussions(): HasMany
    {
        return $this->hasMany(MdtDiscussion::class);
    }

    public function patientDocuments(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function oncallSchedules(): HasMany
    {
        return $this->hasMany(OncallSchedule::class);
    }

    public function consultantSchedules(): HasMany
    {
        return $this->hasMany(ConsultantSchedule::class);
    }

    public function fellowsRotations(): HasMany
    {
        return $this->hasMany(FellowsRotation::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class);
    }
}
