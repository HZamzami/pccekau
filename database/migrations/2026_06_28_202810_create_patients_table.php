<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn')->unique();
            $table->string('name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->string('nationality')->nullable();
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->unsignedTinyInteger('baseline_oxygen_saturation')->nullable();
            $table->text('primary_diagnosis')->nullable();
            $table->text('surgical_history')->nullable();
            $table->text('current_plan')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('referring_physician')->nullable();
            $table->enum('status', ['active', 'follow-up', 'post-op', 'discharged'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};