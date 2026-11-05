<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RetireLegacyCathTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_cath_rows_become_diagnostic_caths(): void
    {
        $patient = Patient::factory()->create();
        $stamps = ['clinic_id' => $this->clinic->id, 'patient_id' => $patient->id, 'created_at' => now(), 'updated_at' => now()];

        $imagingCath = DB::table('imaging_reports')->insertGetId($stamps + ['type' => 'cath', 'date' => '2026-10-01', 'report' => 'Normal pressures', 'status' => 'draft', 'procedure_status' => 'completed']);
        DB::table('imaging_reports')->insert($stamps + ['type' => 'mri', 'date' => '2026-10-01', 'report' => 'Fine', 'status' => 'draft', 'procedure_status' => 'completed']);
        DB::table('interventions')->insert($stamps + ['type' => 'cath_intervention', 'name' => 'Old cath', 'date' => '2026-10-02', 'procedure_status' => 'scheduled']);
        $bookingId = DB::table('procedure_bookings')->insertGetId($stamps + ['booking_date' => '2026-10-01', 'category' => 'cath', 'intervention_type' => 'cath_intervention', 'imaging_report_id' => $imagingCath, 'procedure_status' => 'completed']);

        (require database_path('migrations/2026_11_06_000001_retire_legacy_cath_types.php'))->up();

        $this->assertSame(['mri'], DB::table('imaging_reports')->pluck('type')->all());
        $this->assertSame(['diagnostic_cath', 'diagnostic_cath'], DB::table('interventions')->orderBy('id')->pluck('type')->all());

        $converted = DB::table('interventions')->where('notes', 'Normal pressures')->sole();
        $booking = DB::table('procedure_bookings')->find($bookingId);
        $this->assertNull($booking->imaging_report_id);
        $this->assertSame($converted->id, $booking->intervention_id);
        $this->assertSame('diagnostic_cath', $booking->intervention_type);
    }
}
