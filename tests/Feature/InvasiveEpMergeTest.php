<?php

namespace Tests\Feature;

use App\Enums\EpStudyType;
use App\Enums\InterventionType;
use App\Models\EpStudy;
use App\Models\Intervention;
use App\Models\Patient;
use App\Services\Import\CathListImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

// Invasive EP (EP studies, ablations, pacing implants) is an EpStudy, not an
// Intervention.
class InvasiveEpMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cath_list_ablations_import_as_ep_studies(): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['MRN', 'Name', 'Procedure'],
            ['1001', 'Ablation Child', 'SVT ablation'],
            ['1002', 'Device Child', 'ASD device closure'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'cath').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $importer = new CathListImporter;
        $importer->commit($importer->preview($path));
        unlink($path);

        $study = EpStudy::sole();
        $this->assertSame(EpStudyType::Ablation, $study->type);
        $this->assertSame('SVT ablation', $study->procedure);
        $this->assertSame(InterventionType::InterventionalCathDevice, Intervention::sole()->type);
    }

    public function test_migration_moves_invasive_ep_interventions_into_ep_studies(): void
    {
        $patient = Patient::factory()->create();
        $row = fn (string $type, string $name) => DB::table('interventions')->insertGetId([
            'clinic_id' => $this->clinic->id, 'patient_id' => $patient->id, 'type' => $type, 'name' => $name,
            'date' => '2026-10-01', 'procedure_status' => 'scheduled', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ablation = $row('ablation', 'AVNRT ablation');
        $row('ep_procedure', 'Old EP');
        $row('surgery', 'Glenn');
        $bookingId = DB::table('procedure_bookings')->insertGetId([
            'clinic_id' => $this->clinic->id, 'patient_id' => $patient->id, 'booking_date' => '2026-10-01',
            'category' => 'ep', 'intervention_type' => 'ep_procedure', 'intervention_id' => $ablation,
            'procedure_status' => 'scheduled', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_11_05_000001_merge_invasive_ep_into_ep_studies.php');
        $migration->down();
        $migration->up();

        $this->assertSame(['surgery'], DB::table('interventions')->pluck('type')->all());
        $this->assertEqualsCanonicalizing(['ablation', 'ep_study'], EpStudy::pluck('type')->map->value->all());

        $moved = EpStudy::where('procedure', 'AVNRT ablation')->sole();
        $booking = DB::table('procedure_bookings')->find($bookingId);
        $this->assertNull($booking->intervention_id);
        $this->assertSame($moved->id, $booking->ep_study_id);
        $this->assertSame('ep_study', $booking->intervention_type);
    }
}
