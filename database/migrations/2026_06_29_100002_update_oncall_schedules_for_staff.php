<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oncall_schedules', function (Blueprint $table) {
            // Weekly role assignments — one staff member per role
            $table->foreignId('clinic_staff_id')->nullable()->after('hijri_date_range')->constrained('staff')->nullOnDelete();
            $table->foreignId('inpatient_staff_id')->nullable()->after('clinic_staff_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('consultation_staff_id')->nullable()->after('inpatient_staff_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('cath_staff_id')->nullable()->after('consultation_staff_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('service_staff_id')->nullable()->after('cath_staff_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('ep_staff_id')->nullable()->after('service_staff_id')->constrained('staff')->nullOnDelete();

            // Daily on-call assignments
            $table->foreignId('oncall_sunday_id')->nullable()->after('ep_staff_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('oncall_monday_id')->nullable()->after('oncall_sunday_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('oncall_tuesday_id')->nullable()->after('oncall_monday_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('oncall_wednesday_id')->nullable()->after('oncall_tuesday_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('oncall_thursday_id')->nullable()->after('oncall_wednesday_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('oncall_friday_id')->nullable()->after('oncall_thursday_id')->constrained('staff')->nullOnDelete();
            $table->foreignId('oncall_saturday_id')->nullable()->after('oncall_friday_id')->constrained('staff')->nullOnDelete();

            // Drop the old text columns
            $table->dropColumn([
                'clinic_doctor',
                'inpatient_doctor',
                'consultation_doctor',
                'cath_doctor',
                'service_doctor',
                'ep_doctor',
                'oncall_sunday',
                'oncall_monday',
                'oncall_tuesday',
                'oncall_wednesday',
                'oncall_thursday',
                'oncall_friday',
                'oncall_saturday',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('oncall_schedules', function (Blueprint $table) {
            $table->string('clinic_doctor')->nullable();
            $table->string('inpatient_doctor')->nullable();
            $table->string('consultation_doctor')->nullable();
            $table->string('cath_doctor')->nullable();
            $table->string('service_doctor')->nullable();
            $table->string('ep_doctor')->nullable();
            $table->string('oncall_sunday')->nullable();
            $table->string('oncall_monday')->nullable();
            $table->string('oncall_tuesday')->nullable();
            $table->string('oncall_wednesday')->nullable();
            $table->string('oncall_thursday')->nullable();
            $table->string('oncall_friday')->nullable();
            $table->string('oncall_saturday')->nullable();

            $table->dropForeign(['clinic_staff_id']);
            $table->dropForeign(['inpatient_staff_id']);
            $table->dropForeign(['consultation_staff_id']);
            $table->dropForeign(['cath_staff_id']);
            $table->dropForeign(['service_staff_id']);
            $table->dropForeign(['ep_staff_id']);
            $table->dropForeign(['oncall_sunday_id']);
            $table->dropForeign(['oncall_monday_id']);
            $table->dropForeign(['oncall_tuesday_id']);
            $table->dropForeign(['oncall_wednesday_id']);
            $table->dropForeign(['oncall_thursday_id']);
            $table->dropForeign(['oncall_friday_id']);
            $table->dropForeign(['oncall_saturday_id']);

            $table->dropColumn([
                'clinic_staff_id',
                'inpatient_staff_id',
                'consultation_staff_id',
                'cath_staff_id',
                'service_staff_id',
                'ep_staff_id',
                'oncall_sunday_id',
                'oncall_monday_id',
                'oncall_tuesday_id',
                'oncall_wednesday_id',
                'oncall_thursday_id',
                'oncall_friday_id',
                'oncall_saturday_id',
            ]);
        });
    }
};
