<?php

namespace Tests\Feature;

use App\Models\EchoMeasurement;
use App\Models\ImagingReport;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EchoBaselineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_newest_echo_updates_patient_baseline_and_stores_bsa(): void
    {
        $patient = Patient::factory()->create(['weight_kg' => 10, 'height_cm' => 80]);
        $report = ImagingReport::factory()->for($patient)->create(['type' => 'echo', 'date' => today()]);

        $measurement = EchoMeasurement::create([
            'imaging_report_id' => $report->id,
            'height_cm' => 100,
            'weight_kg' => 16,
        ]);

        $this->assertEqualsWithDelta(0.67, $measurement->fresh()->bsa, 0.01);
        $this->assertEquals(16, $patient->fresh()->weight_kg);
        $this->assertEquals(100, $patient->fresh()->height_cm);
    }

    public function test_backfilled_old_echo_does_not_overwrite_baseline(): void
    {
        $patient = Patient::factory()->create(['weight_kg' => 20, 'height_cm' => 110]);

        $newReport = ImagingReport::factory()->for($patient)->create(['type' => 'echo', 'date' => today()]);
        EchoMeasurement::create(['imaging_report_id' => $newReport->id, 'height_cm' => 110, 'weight_kg' => 20]);

        $oldReport = ImagingReport::factory()->for($patient)->create(['type' => 'echo', 'date' => today()->subYear()]);
        EchoMeasurement::create(['imaging_report_id' => $oldReport->id, 'height_cm' => 90, 'weight_kg' => 12]);

        $this->assertEquals(20, $patient->fresh()->weight_kg);
        $this->assertEquals(110, $patient->fresh()->height_cm);
    }
}
