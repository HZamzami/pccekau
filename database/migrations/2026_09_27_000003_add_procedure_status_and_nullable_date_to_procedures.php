<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['interventions', 'imaging_reports', 'ep_studies'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('procedure_status')->default('ordered')->after('date');
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->date('date')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['interventions', 'imaging_reports', 'ep_studies'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->date('date')->nullable(false)->change();
                $table->dropColumn('procedure_status');
            });
        }
    }
};
