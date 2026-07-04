<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->decimal('weight_kg', 5, 2)->nullable()->after('visit_date');
            $table->decimal('height_cm', 5, 1)->nullable()->after('weight_kg');
            $table->unsignedTinyInteger('oxygen_saturation')->nullable()->after('height_cm');
            $table->unsignedSmallInteger('heart_rate')->nullable()->after('oxygen_saturation');
            $table->unsignedSmallInteger('bp_systolic')->nullable()->after('heart_rate');
            $table->unsignedSmallInteger('bp_diastolic')->nullable()->after('bp_systolic');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->dropColumn([
                'weight_kg',
                'height_cm',
                'oxygen_saturation',
                'heart_rate',
                'bp_systolic',
                'bp_diastolic',
            ]);
        });
    }
};
