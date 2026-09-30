<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Patient;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Stephenjude\FilamentTwoFactorAuthentication\Pages\Challenge;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function userWithTwoFactor(string $secret): User
    {
        $user = User::factory()->create(['role' => UserRole::Doctor]);
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    public function test_users_without_two_factor_are_sent_to_setup_when_enforced(): void
    {
        config(['auth.two_factor_enforced' => true]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)
            ->get(route('patients.summary-pdf', $patient))
            ->assertRedirect(route('filament.admin.two-factor.setup'));
    }

    public function test_password_alone_cannot_open_patient_pdfs_when_two_factor_is_on(): void
    {
        $doctor = $this->userWithTwoFactor((new Google2FA)->generateSecretKey());
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)
            ->get(route('patients.summary-pdf', $patient))
            ->assertRedirect(route('filament.admin.two-factor.challenge'));

        $this->actingAs($doctor)
            ->get(PatientResource::getUrl('index'))
            ->assertRedirect(route('filament.admin.two-factor.challenge'));
    }

    public function test_a_valid_authenticator_code_unlocks_the_session(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $doctor = $this->userWithTwoFactor($secret);
        $patient = Patient::factory()->create();

        $this->actingAs($doctor);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Challenge::class)
            ->fillForm(['code' => '000000'])
            ->call('authenticate')
            ->assertHasFormErrors(['code']);

        Livewire::test(Challenge::class)
            ->fillForm(['code' => $google2fa->getCurrentOtp($secret)])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertTrue($doctor->isTwoFactorChallengePassed());

        $this->get(route('patients.summary-pdf', $patient))->assertOk();
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get(route('filament.admin.auth.login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_admins_cannot_create_users_with_weak_passwords(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'New Nurse',
                'email' => 'nurse@example.com',
                'role' => UserRole::Nurse,
                'password' => 'password',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    public function test_two_factor_secrets_are_never_serialized(): void
    {
        $user = $this->userWithTwoFactor('SECRETSECRETSECR');

        $this->assertArrayNotHasKey('two_factor_secret', $user->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->toArray());
    }
}
