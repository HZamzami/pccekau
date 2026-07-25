<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\ImagingReport;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImagingReportLockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_cannot_update_a_final_report(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $report = ImagingReport::factory()->create(['status' => ReportStatus::Final]);

        $this->assertFalse($doctor->can('update', $report));
    }

    public function test_doctor_can_update_a_draft_report(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $report = ImagingReport::factory()->create(['status' => ReportStatus::Draft]);

        $this->assertTrue($doctor->can('update', $report));
    }

    public function test_amend_unlocks_a_final_report_and_logs_the_reason(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $this->actingAs($doctor);

        $staff = Staff::factory()->create();
        $report = ImagingReport::factory()->create([
            'status' => ReportStatus::Final,
            'finalized_at' => now(),
        ]);
        $report->readers()->attach($staff);

        $report->amend('Measurement transcription error');

        $this->assertSame(ReportStatus::Amended, $report->fresh()->status);
        $this->assertTrue($doctor->can('update', $report->fresh()));
        $this->assertDatabaseHas('activity_log', [
            'description' => 'amended',
            'subject_id' => $report->id,
        ]);
    }

    public function test_finalize_sets_status_and_timestamp(): void
    {
        $report = ImagingReport::factory()->create(['status' => ReportStatus::Preliminary]);

        $report->finalize();

        $this->assertSame(ReportStatus::Final, $report->fresh()->status);
        $this->assertNotNull($report->fresh()->finalized_at);
    }
}
