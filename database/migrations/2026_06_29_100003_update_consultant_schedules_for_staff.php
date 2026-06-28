<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultant_schedules', function (Blueprint $table) {
            $table->foreignId('service_staff_id')->nullable()->after('hijri_date')->constrained('staff')->nullOnDelete();
            $table->foreignId('cath_staff_id')->nullable()->after('service_staff_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('ep_staff_id')->nullable()->after('cath_staff_id')->constrained('staff')->nullOnDelete();

            $table->dropColumn(['service_consultant', 'cath_consultant', 'ep_consultant']);
        });
    }

    public function down(): void
    {
        Schema::table('consultant_schedules', function (Blueprint $table) {
            $table->string('service_consultant')->nullable();
            $table->string('cath_consultant')->nullable();
            $table->string('ep_consultant')->nullable();

            $table->dropForeign(['service_staff_id']);
            $table->dropForeign(['cath_staff_id']);
            $table->dropForeign(['ep_staff_id']);
            $table->dropColumn(['service_staff_id', 'cath_staff_id', 'ep_staff_id']);
        });
    }
};
