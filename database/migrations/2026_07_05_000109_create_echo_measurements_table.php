<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echo_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_report_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('bsa', 4, 3)->nullable();

            // Linear dimensions (cm)
            $table->decimal('ivsd', 5, 2)->nullable();
            $table->decimal('lvidd', 5, 2)->nullable();
            $table->decimal('lvpwd', 5, 2)->nullable();
            $table->decimal('lvids', 5, 2)->nullable();
            $table->decimal('la', 5, 2)->nullable();
            $table->decimal('ao_annulus', 5, 2)->nullable();
            $table->decimal('ao_root', 5, 2)->nullable();

            // Function (%)
            $table->decimal('ef', 5, 2)->nullable();
            $table->decimal('fs', 5, 2)->nullable();

            // Doppler: peak velocity (m/s) and peak gradient (mmHg) per valve
            foreach (['mv', 'tv', 'pv', 'av'] as $valve) {
                $table->decimal("{$valve}_peak_velocity", 5, 2)->nullable();
                $table->decimal("{$valve}_peak_gradient", 5, 2)->nullable();
            }

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echo_measurements');
    }
};
