<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The original enum() role column carries a CHECK constraint limited to
    // consultant/fellow. Removing it avoids another migration every time a
    // role is added; validity is enforced by Staff::$roleLabels at the
    // application layer. Also remaps specialties: echo and imaging were
    // merged into advanced_imaging, and opd was retired.
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE staff DROP CONSTRAINT IF EXISTS staff_role_check');
        } else {
            // sqlite (tests): change() rebuilds the table, shedding the CHECK.
            Schema::table('staff', function (Blueprint $table) {
                $table->string('role')->change();
            });
        }

        DB::table('staff')
            ->whereIn('specialty', ['echo', 'imaging'])
            ->update(['specialty' => 'advanced_imaging']);

        DB::table('staff')
            ->where('specialty', 'opd')
            ->update(['specialty' => null]);
    }

    public function down(): void
    {
        // Irreversible: former echo and imaging specialties are
        // indistinguishable once merged, and retired opd values were cleared.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE staff ADD CONSTRAINT staff_role_check CHECK (role::text = ANY (ARRAY['consultant', 'fellow']::text[]))");
        }
    }
};
