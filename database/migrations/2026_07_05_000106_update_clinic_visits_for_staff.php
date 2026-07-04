<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->foreignId('seen_by_id')->nullable()->after('next_follow_up_date')->constrained('staff')->nullOnDelete();
        });

        DB::statement('
            UPDATE clinic_visits
            SET seen_by_id = (SELECT id FROM staff WHERE staff.name = clinic_visits.seen_by LIMIT 1)
        ');

        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->dropColumn('seen_by');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_visits', function (Blueprint $table) {
            $table->string('seen_by')->nullable()->after('next_follow_up_date');
            $table->dropForeign(['seen_by_id']);
            $table->dropColumn('seen_by_id');
        });
    }
};
