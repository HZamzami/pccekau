<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Imported patients often arrive with only an MRN, name, and a
    // clinical record attached — date of birth and gender get filled in
    // later once known.
    //
    // On pgsql, doctrine/dbal's ->change() tries to redefine the enum-backed
    // "gender" column's type and its CHECK constraint in a single ALTER
    // COLUMN clause, which Postgres doesn't support as valid syntax — so
    // pgsql drops NOT NULL directly instead (the CHECK constraint itself
    // already allows null since it isn't NOT NULL-aware).
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE patients ALTER COLUMN date_of_birth DROP NOT NULL');
            DB::statement('ALTER TABLE patients ALTER COLUMN gender DROP NOT NULL');

            return;
        }

        // sqlite (tests): change() rebuilds the table.
        Schema::table('patients', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
            $table->enum('gender', ['male', 'female'])->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE patients ALTER COLUMN date_of_birth SET NOT NULL');
            DB::statement('ALTER TABLE patients ALTER COLUMN gender SET NOT NULL');

            return;
        }

        Schema::table('patients', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable(false)->change();
            $table->enum('gender', ['male', 'female'])->nullable(false)->change();
        });
    }
};
