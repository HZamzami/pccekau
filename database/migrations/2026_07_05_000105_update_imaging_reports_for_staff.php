<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->foreignId('performed_by_id')->nullable()->after('date')->constrained('staff')->nullOnDelete();
        });

        DB::statement('
            UPDATE imaging_reports
            SET performed_by_id = (SELECT id FROM staff WHERE staff.name = imaging_reports.performed_by LIMIT 1)
            WHERE performed_by IS NOT NULL
        ');

        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->dropColumn('performed_by');
        });
    }

    public function down(): void
    {
        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->string('performed_by')->nullable()->after('date');
            $table->dropForeign(['performed_by_id']);
            $table->dropColumn('performed_by_id');
        });
    }
};
