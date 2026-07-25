<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The weekly coverage sheet only tracks Clinic, Inpatient, Consultation,
// Cath and daily On-Call; the Service and EP roles were never filled in
// and are dropped. Consultant schedules keep their own Service/EP columns.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oncall_schedules', function (Blueprint $table) {
            $table->dropForeign(['service_staff_id']);
            $table->dropForeign(['ep_staff_id']);
            $table->dropColumn(['service_staff_id', 'ep_staff_id']);
        });
    }

    public function down(): void
    {
        Schema::table('oncall_schedules', function (Blueprint $table) {
            $table->foreignId('service_staff_id')->nullable()->after('cath_staff_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('ep_staff_id')->nullable()->after('service_staff_id')->constrained('staff')->nullOnDelete();
        });
    }
};
