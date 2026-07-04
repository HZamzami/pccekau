<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The original enum() column carries a CHECK constraint that rejects the
    // new deceased/transferred statuses. Validity is enforced by the
    // PatientStatus enum at the application layer.
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE patients DROP CONSTRAINT IF EXISTS patients_status_check');

            return;
        }

        // sqlite (tests): change() rebuilds the table, shedding the CHECK.
        Schema::table('patients', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE patients ADD CONSTRAINT patients_status_check CHECK (status::text = ANY (ARRAY['active', 'follow-up', 'post-op', 'discharged', 'deceased', 'transferred']::text[]))");
        }
    }
};
