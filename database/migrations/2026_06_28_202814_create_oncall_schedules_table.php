<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per week. Oncall varies daily so stored per day.
        // All other roles (clinic, inpatient, etc.) are typically constant for the week.
        Schema::create('oncall_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('week_start');
            $table->string('hijri_date_range')->nullable();
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
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oncall_schedules');
    }
};
