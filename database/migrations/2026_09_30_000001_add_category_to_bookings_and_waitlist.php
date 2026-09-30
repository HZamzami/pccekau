<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->string('category')->nullable()->after('slot_number');
        });

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->string('category')->nullable()->after('staff_id');
        });

        // MRI/CT slot rows stay null: the old sheet didn't record which of the two it was.
        DB::table('procedure_bookings')
            ->whereIn('slot_type', ['cath_day_care', 'cath_inpatient'])
            ->update(['category' => 'cath']);
    }

    public function down(): void
    {
        Schema::table('procedure_bookings', fn (Blueprint $table) => $table->dropColumn('category'));
        Schema::table('waitlist_entries', fn (Blueprint $table) => $table->dropColumn('category'));
    }
};
