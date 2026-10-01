<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A deleted (soft-deleted) booking kept its slot locked forever, so rebooking
// it crashed. Only live bookings now count toward a slot being taken.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->dropUnique(['clinic_id', 'booking_date', 'slot_type', 'slot_number']);
        });

        DB::statement('CREATE UNIQUE INDEX procedure_bookings_live_slot_unique ON procedure_bookings (clinic_id, booking_date, slot_type, slot_number) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX procedure_bookings_live_slot_unique');

        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->unique(['clinic_id', 'booking_date', 'slot_type', 'slot_number']);
        });
    }
};
