<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bookings can now be any procedure type, including EP tests and case
// discussions that have no calendar slot, so slot type and slot # are
// optional. Bookings link to their chart record on every tab.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->string('slot_type')->nullable()->change();
            $table->unsignedTinyInteger('slot_number')->nullable()->default(null)->change();
            $table->foreignId('ep_study_id')->nullable()->after('imaging_report_id')->constrained()->nullOnDelete();
            $table->foreignId('mdt_discussion_id')->nullable()->after('ep_study_id')->constrained()->nullOnDelete();
        });

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->string('intervention_type')->nullable()->after('category');
        });

        // SQLite rebuilds the table on these changes and drops the partial
        // index's WHERE clause; restate it so deleted bookings still free slots.
        DB::statement('DROP INDEX IF EXISTS procedure_bookings_live_slot_unique');
        DB::statement('CREATE UNIQUE INDEX procedure_bookings_live_slot_unique ON procedure_bookings (clinic_id, booking_date, slot_type, slot_number) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('waitlist_entries', fn (Blueprint $table) => $table->dropColumn('intervention_type'));

        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mdt_discussion_id');
            $table->dropConstrainedForeignId('ep_study_id');
        });

        DB::table('procedure_bookings')->whereNull('slot_type')->update(['slot_type' => 'cath_day_care']);
        DB::table('procedure_bookings')->whereNull('slot_number')->update(['slot_number' => 1]);

        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->string('slot_type')->nullable(false)->change();
            $table->unsignedTinyInteger('slot_number')->default(1)->nullable(false)->change();
        });

        DB::statement('DROP INDEX IF EXISTS procedure_bookings_live_slot_unique');
        DB::statement('CREATE UNIQUE INDEX procedure_bookings_live_slot_unique ON procedure_bookings (clinic_id, booking_date, slot_type, slot_number) WHERE deleted_at IS NULL');
    }
};
