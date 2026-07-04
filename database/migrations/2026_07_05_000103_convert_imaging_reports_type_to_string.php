<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The original enum() column carries a CHECK constraint that doesn't
    // allow the new plain 'echo' type. Removing it avoids another migration
    // every time a modality is added; validity is enforced by the
    // ImagingType enum at the application layer.
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE imaging_reports DROP CONSTRAINT IF EXISTS imaging_reports_type_check');

            return;
        }

        // sqlite (tests): change() rebuilds the table, shedding the CHECK.
        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->string('type', 20)->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE imaging_reports ADD CONSTRAINT imaging_reports_type_check CHECK (type::text = ANY (ARRAY['mri', 'ct', 'cath', 'echo', 'echo_3d', 'holter', 'stress_ecg']::text[]))");
        }
    }
};
