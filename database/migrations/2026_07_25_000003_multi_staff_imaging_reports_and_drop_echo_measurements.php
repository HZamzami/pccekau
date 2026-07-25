<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Imaging reports can be performed and read/signed by multiple staff, so the
// single performed_by_id / signed_by columns move to pivot tables. The echo
// measurements module is retired along with its data.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_report_performers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->unique(['imaging_report_id', 'staff_id']);
        });

        Schema::create('imaging_report_readers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->unique(['imaging_report_id', 'staff_id']);
        });

        DB::table('imaging_reports')->whereNotNull('performed_by_id')->orderBy('id')
            ->each(fn (object $report) => DB::table('imaging_report_performers')->insert([
                'imaging_report_id' => $report->id,
                'staff_id' => $report->performed_by_id,
            ]));

        DB::table('imaging_reports')->whereNotNull('signed_by')->orderBy('id')
            ->each(fn (object $report) => DB::table('imaging_report_readers')->insert([
                'imaging_report_id' => $report->id,
                'staff_id' => $report->signed_by,
            ]));

        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->dropForeign(['performed_by_id']);
            $table->dropForeign(['signed_by']);
            $table->dropColumn(['performed_by_id', 'signed_by']);
        });

        Schema::dropIfExists('echo_measurements');
    }

    public function down(): void
    {
        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->foreignId('performed_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('signed_by')->nullable()->constrained('staff')->nullOnDelete();
        });

        Schema::dropIfExists('imaging_report_performers');
        Schema::dropIfExists('imaging_report_readers');
    }
};
