<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Holter and stress ECG are rhythm diagnostics, not imaging modalities.
    // They move out of imaging_reports into their own ep_studies table,
    // keeping the same draft → final report workflow.
    public function up(): void
    {
        Schema::create('ep_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('draft')->index();
            $table->date('date');
            $table->foreignId('performed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->longText('report');
            $table->text('notes')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $reports = DB::table('imaging_reports')
            ->whereIn('type', ['holter', 'stress_ecg'])
            ->get();

        foreach ($reports as $report) {
            DB::table('ep_studies')->insert([
                'patient_id'      => $report->patient_id,
                'type'            => $report->type,
                'status'          => $report->status,
                'date'            => $report->date,
                'performed_by_id' => $report->performed_by_id,
                'report'          => $report->report,
                'notes'           => $report->notes,
                'signed_by'       => $report->signed_by,
                'finalized_at'    => $report->finalized_at,
                'created_at'      => $report->created_at,
                'updated_at'      => $report->updated_at,
                'deleted_at'      => $report->deleted_at,
            ]);
        }

        DB::table('imaging_reports')
            ->whereIn('type', ['holter', 'stress_ecg'])
            ->delete();
    }

    public function down(): void
    {
        // Moved rows are not restored to imaging_reports; the table is
        // simply dropped.
        Schema::dropIfExists('ep_studies');
    }
};
