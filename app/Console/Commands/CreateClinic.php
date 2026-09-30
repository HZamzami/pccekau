<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

// Replaces public sign-up: creates a clinic workspace and its first admin.
// Everyone else is added by that admin from inside the panel.
class CreateClinic extends Command
{
    protected $signature = 'pccekau:create-clinic';

    protected $description = 'Create a clinic workspace and its first admin account';

    public function handle(): int
    {
        $data = [
            'clinic_name' => text('Clinic / hospital name', required: true),
            'name' => text('Admin full name', required: true),
            'email' => text('Admin email', required: true),
            'password' => password('Admin password (min 12 characters, letters and numbers)', required: true),
        ];

        $validator = Validator::make($data, [
            'clinic_name' => ['required', 'max:255'],
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($data) {
            $clinic = Clinic::create([
                'name' => $data['clinic_name'],
                'slug' => $this->uniqueSlug($data['clinic_name']),
            ]);

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Admin,
            ]);
            $user->clinic()->associate($clinic);
            $user->save();

            $clinic->update(['created_by' => $user->id]);

            return $user;
        });

        $this->info("Created clinic \"{$user->clinic->name}\" ({$user->clinic->slug}) with admin {$user->email}.");
        $this->line('They will be asked to set up two-factor authentication on first login.');

        return self::SUCCESS;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'clinic';
        $slug = $base;

        for ($i = 2; Clinic::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
