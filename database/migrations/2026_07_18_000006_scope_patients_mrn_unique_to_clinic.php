<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // MRNs are hospital-issued identifiers: unique within a clinic, freely
    // reusable across clinics.
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['mrn']);
            $table->unique(['clinic_id', 'mrn']);
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['clinic_id', 'mrn']);
            $table->unique(['mrn']);
        });
    }
};
