<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Retires two types kept only so old rows still displayed: caths filed as
    // imaging reports (caths live in Interventions) and the broad "Cath
    // intervention" category from before the exact cath types existed. Both
    // become diagnostic caths.
    public function up(): void
    {
        foreach (DB::table('imaging_reports')->where('type', 'cath')->get() as $report) {
            $interventionId = DB::table('interventions')->insertGetId([
                'clinic_id' => $report->clinic_id,
                'patient_id' => $report->patient_id,
                'type' => 'diagnostic_cath',
                'name' => 'Diagnostic cath',
                'date' => $report->date,
                'procedure_status' => $report->procedure_status,
                'notes' => trim(implode("\n\n", array_filter([$report->report, $report->notes]))) ?: null,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
                'deleted_at' => $report->deleted_at,
            ]);

            DB::table('procedure_bookings')
                ->where('imaging_report_id', $report->id)
                ->update(['imaging_report_id' => null, 'intervention_id' => $interventionId]);
        }

        DB::table('imaging_reports')->where('type', 'cath')->delete();

        DB::table('interventions')->where('type', 'cath_intervention')->update(['type' => 'diagnostic_cath']);

        foreach (['procedure_bookings', 'waitlist_entries'] as $table) {
            DB::table($table)->where('intervention_type', 'cath_intervention')->update(['intervention_type' => 'diagnostic_cath']);
        }
    }

    public function down(): void
    {
        // Converted rows stay as diagnostic caths.
    }
};
