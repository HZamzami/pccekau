<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TENANT_TABLES = [
        'patients',
        'clinic_visits',
        'imaging_reports',
        'echo_measurements',
        'ep_studies',
        'mdt_discussions',
        'patient_documents',
        'interventions',
        'oncall_schedules',
        'consultant_schedules',
        'fellows_rotations',
        'staff',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->constrained()->restrictOnDelete();
        });

        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('clinic_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        Schema::table('activity_log', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
        });

        // Backfill: pre-tenancy installs get a default clinic holding all
        // existing rows. Skipped on fresh/empty databases (tests, new setups).
        if (DB::table('users')->exists()) {
            $name = env('DEFAULT_CLINIC_NAME', 'PCCEKAU');

            $clinicId = DB::table('clinics')->insertGetId([
                'name'       => $name,
                'slug'       => Str::slug($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([...self::TENANT_TABLES, 'users', 'activity_log'] as $tableName) {
                DB::table($tableName)->update(['clinic_id' => $clinicId]);
            }

            // Accounts that predate email verification are trusted as-is.
            DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
        }

        foreach ([...self::TENANT_TABLES, 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('clinic_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'activity_log', ...self::TENANT_TABLES] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('clinic_id');
            });
        }
    }
};
