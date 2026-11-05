<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INVASIVE = ['ep_study', 'ablation', 'transvenous_pacing_implant', 'ep_procedure'];

    // Invasive EP (EP studies, ablations, pacing implants) moves out of
    // interventions into ep_studies, so all EP is one kind of record. The old
    // "EP procedure" catch-all becomes an EP study.
    public function up(): void
    {
        Schema::table('ep_studies', function (Blueprint $table) {
            $table->string('procedure')->nullable()->after('type');
        });

        foreach (DB::table('interventions')->whereIn('type', self::INVASIVE)->get() as $intervention) {
            $epStudyId = DB::table('ep_studies')->insertGetId([
                'clinic_id' => $intervention->clinic_id,
                'patient_id' => $intervention->patient_id,
                'type' => $intervention->type === 'ep_procedure' ? 'ep_study' : $intervention->type,
                'procedure' => $intervention->name,
                'status' => 'draft',
                'date' => $intervention->date,
                'procedure_status' => $intervention->procedure_status,
                'performed_by_id' => $intervention->operator_id,
                'report' => '',
                'notes' => $intervention->notes,
                'created_at' => $intervention->created_at,
                'updated_at' => $intervention->updated_at,
                'deleted_at' => $intervention->deleted_at,
            ]);

            DB::table('procedure_bookings')
                ->where('intervention_id', $intervention->id)
                ->update(['intervention_id' => null, 'ep_study_id' => $epStudyId]);
        }

        DB::table('interventions')->whereIn('type', self::INVASIVE)->delete();

        foreach (['procedure_bookings', 'waitlist_entries'] as $table) {
            DB::table($table)->where('intervention_type', 'ep_procedure')->update(['intervention_type' => 'ep_study']);
        }
    }

    public function down(): void
    {
        // Moved rows stay in ep_studies; only the new column is dropped.
        Schema::table('ep_studies', function (Blueprint $table) {
            $table->dropColumn('procedure');
        });
    }
};
