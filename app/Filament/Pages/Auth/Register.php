<?php

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Models\Clinic;
use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Public signup: each registration creates a fresh clinic workspace and
// makes the registrant its admin. Further users are created by that admin
// from inside the panel, never through this page.
class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
            $this->getClinicNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
        ]);
    }

    protected function getClinicNameFormComponent(): Component
    {
        return TextInput::make('clinic_name')
            ->label('Clinic / hospital name')
            ->helperText('The name of your workspace — e.g. your department or hospital.')
            ->required()
            ->maxLength(255);
    }

    protected function handleRegistration(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $clinic = Clinic::create([
                'name' => $data['clinic_name'],
                'slug' => $this->generateSlug($data['clinic_name']),
            ]);

            $user = new User([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
                'role'     => UserRole::Admin,
            ]);
            $user->clinic()->associate($clinic);
            $user->save();

            $clinic->update(['created_by' => $user->id]);

            return $user;
        });
    }

    private function generateSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'clinic';
        $slug = $base;

        for ($i = 2; Clinic::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
