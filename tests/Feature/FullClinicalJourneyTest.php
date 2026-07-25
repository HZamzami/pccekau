<?php

namespace Tests\Feature;

use App\Enums\PatientStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Filament\Resources\PatientResource;
use App\Models\ClinicVisit;
use App\Models\ImagingReport;
use App\Models\MdtDiscussion;
use App\Models\Patient;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// The full life of one case, start to finish: registration, echo study,
// sign-off, MDT presentation, clinic follow-up with vitals, daily digest,
// and the printable summary.
class FullClinicalJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_clinical_journey(): void
    {
        // 1. Department setup
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $reader = Staff::factory()->create(['role' => 'consultant', 'specialty' => 'advanced_imaging']);
        $this->actingAs($doctor);

        // 2. Register a patient with structured lesions
        $patient = Patient::factory()->create([
            'lesions' => ['tof', 'pda'],
            'weight_kg' => 10,
            'height_cm' => 80,
            'status' => PatientStatus::Active,
        ]);

        // 3. Echo report performed by staff
        $report = ImagingReport::factory()->for($patient)->create([
            'type' => 'echo',
            'status' => ReportStatus::Draft,
            'date' => today(),
        ]);
        $report->performers()->attach($reader);

        // 4. Assign reader and finalize — report locks
        $report->readers()->attach($reader);
        $this->assertTrue($report->hasSigner());
        $report->finalize();
        $this->assertTrue($report->fresh()->isLocked());
        $this->assertFalse($doctor->can('update', $report->fresh()));

        // 5. Present at MDT, linked to the actual report
        $mdt = MdtDiscussion::factory()->for($patient)->create([
            'discussion_date' => today(),
            'specialist_fellow_id' => $reader->id,
        ]);
        $mdt->imagingReports()->attach($report);
        $this->assertCount(1, $mdt->fresh()->imagingReports);

        // 6. Clinic visit with vitals and a follow-up date
        ClinicVisit::factory()->for($patient)->create([
            'seen_by_id' => $reader->id,
            'weight_kg' => 16.2,
            'oxygen_saturation' => 88,
            'next_follow_up_date' => today()->subDay(),
        ]);
        $this->assertSame(1, ClinicVisit::dueFollowUps()->count());

        // 7. Daily digest reaches clinicians (follow-up due + MDT today)
        $this->artisan('pccekau:daily-digest')->assertSuccessful();
        $this->assertSame(1, $doctor->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());

        // 8. Chart page and printable artifacts
        $this->get(PatientResource::getUrl('view', ['record' => $patient]))->assertOk()->assertSee('TOF');
        $this->get(route('patients.summary-pdf', $patient))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('imaging-reports.pdf', $report))->assertOk();
        $this->get(route('mdt-discussions.pdf', $mdt))->assertOk();

        // 9. Patient dies — drops out of follow-up queues everywhere
        $patient->update(['status' => PatientStatus::Deceased]);
        $this->assertSame(0, ClinicVisit::dueFollowUps()->count());
    }
}
