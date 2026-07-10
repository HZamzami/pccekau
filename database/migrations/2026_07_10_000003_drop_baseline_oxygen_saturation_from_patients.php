<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Oxygen saturation is a per-encounter vital captured on clinic visits
    // (and MDT discussions), not a static patient attribute.
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('baseline_oxygen_saturation');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedTinyInteger('baseline_oxygen_saturation')->nullable();
        });
    }
};
