<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per week. Tracks the weekly on-call consultant assignments
        // for Service, Cath, and EP (as seen in the Ped Cardiology Oncall table).
        Schema::create('consultant_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('week_start');
            $table->string('hijri_date')->nullable();
            $table->string('service_consultant')->nullable();
            $table->string('cath_consultant')->nullable();
            $table->string('ep_consultant')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_schedules');
    }
};
