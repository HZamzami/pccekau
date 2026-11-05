<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * A demo clinic with made-up patients, for local development. Its admin
     * is admin@pccekau.test / password.
     */
    public function run(): void
    {
        // Made-up data and a known password: never on a live site.
        if (app()->isProduction()) {
            $this->command?->error('Seeding is for local development only. Create a real clinic with: php artisan pccekau:create-clinic');

            return;
        }

        // Idempotent: safe to re-run on an existing database.
        $clinic = Clinic::firstOrCreate(['slug' => 'demo'], ['name' => 'Demo Clinic']);

        User::firstOrCreate(
            ['email' => 'admin@pccekau.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'clinic_id' => $clinic->id,
            ],
        );

        $this->call(DemoDataSeeder::class);
    }
}
