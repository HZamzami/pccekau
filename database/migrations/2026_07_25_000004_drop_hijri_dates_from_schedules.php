<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oncall_schedules', function (Blueprint $table) {
            $table->dropColumn('hijri_date_range');
        });

        Schema::table('consultant_schedules', function (Blueprint $table) {
            $table->dropColumn('hijri_date');
        });
    }

    public function down(): void
    {
        Schema::table('oncall_schedules', function (Blueprint $table) {
            $table->string('hijri_date_range')->nullable();
        });

        Schema::table('consultant_schedules', function (Blueprint $table) {
            $table->string('hijri_date')->nullable();
        });
    }
};
