<?php

namespace Tests\Feature;

use App\Models\ClinicVisit;
use App\Models\ImagingReport;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSoftDeleteCascadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleting_a_patient_soft_deletes_children(): void
    {
        $patient = Patient::factory()->create();
        $report = ImagingReport::factory()->for($patient)->create();
        $visit = ClinicVisit::factory()->for($patient)->create();

        $patient->delete();

        $this->assertSoftDeleted($patient);
        $this->assertSoftDeleted('imaging_reports', ['id' => $report->id]);
        $this->assertSoftDeleted('clinic_visits', ['id' => $visit->id]);
    }

    public function test_restoring_a_patient_restores_children(): void
    {
        $patient = Patient::factory()->create();
        $report = ImagingReport::factory()->for($patient)->create();

        $patient->delete();
        $patient->restore();

        $this->assertNull($patient->fresh()->deleted_at);
        $this->assertNull(ImagingReport::find($report->id)->deleted_at);
    }

    public function test_force_delete_hard_removes_children(): void
    {
        $patient = Patient::factory()->create();
        $report = ImagingReport::factory()->for($patient)->create();

        $patient->forceDelete();

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
        $this->assertDatabaseMissing('imaging_reports', ['id' => $report->id]);
    }
}
