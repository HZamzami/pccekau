<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The OPD rotation was retired. Drop the enum() CHECK constraint so
    // future rotation changes don't need migrations; validity is enforced
    // by FellowsRotation::$rotationLabels at the application layer.
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fellows_rotations DROP CONSTRAINT IF EXISTS fellows_rotations_rotation_check');
        } else {
            // sqlite (tests): change() rebuilds the table, shedding the CHECK.
            Schema::table('fellows_rotations', function (Blueprint $table) {
                $table->string('rotation')->change();
            });
        }

        DB::table('fellows_rotations')->where('rotation', 'opd')->delete();
    }

    public function down(): void
    {
        // Irreversible: rows with the retired opd rotation were deleted.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE fellows_rotations ADD CONSTRAINT fellows_rotations_rotation_check CHECK (rotation::text = ANY (ARRAY['echo', 'ep', 'cath', 'opd', 'icu', 'inpatient', 'research', 'achd', 'imaging', 'elective', 'vacation']::text[]))");
        }
    }
};
