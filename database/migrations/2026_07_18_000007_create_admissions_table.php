<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('admitted_at');
            $table->string('ward')->nullable();
            $table->string('bed')->nullable();
            $table->foreignId('admitted_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('admission_note');

            // Handover board fields — continuously updated during the stay
            $table->text('presentation_diagnosis')->nullable();
            $table->text('active_issues')->nullable();
            $table->text('clinical_examination')->nullable();
            $table->text('investigations')->nullable();
            $table->text('medications')->nullable();
            $table->text('oncall_tasks')->nullable();

            // Open admission = discharged_at IS NULL
            $table->dateTime('discharged_at')->nullable();
            $table->foreignId('discharged_by_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('discharge_note')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['clinic_id', 'discharged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
