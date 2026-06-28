<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('visit_date');
            $table->text('subjective');
            $table->text('objective');
            $table->text('assessment');
            $table->text('plan');
            $table->date('next_follow_up_date')->nullable();
            $table->string('seen_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_visits');
    }
};