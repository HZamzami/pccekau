<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\Auth\Register;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Clinic;
use App\Models\ClinicVisit;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_clinic_with_its_admin(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'name'                 => 'Dr. New Founder',
                'clinic_name'          => 'Riyadh Heart Center',
                'email'                => 'founder@example.com',
                'password'             => 'super-secret-1',
                'passwordConfirmation' => 'super-secret-1',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $clinic = Clinic::where('slug', 'riyadh-heart-center')->first();
        $this->assertNotNull($clinic);

        $user = User::where('email', 'founder@example.com')->first();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->clinic->is($clinic));
        $this->assertSame($user->id, $clinic->created_by);
    }

    public function test_registration_resolves_slug_collisions(): void
    {
        Clinic::factory()->create(['slug' => 'heart-center']);

        Livewire::test(Register::class)
            ->fillForm([
                'name'                 => 'Second Founder',
                'clinic_name'          => 'Heart Center',
                'email'                => 'second@example.com',
                'password'             => 'super-secret-1',
                'passwordConfirmation' => 'super-secret-1',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'heart-center-2',
            User::where('email', 'second@example.com')->first()->clinic->slug,
        );
    }

    public function test_cross_clinic_patient_pages_and_pdfs_are_denied(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        $otherClinic = Clinic::factory()->create();
        $foreignPatient = Patient::factory()->create(['clinic_id' => $otherClinic->id]);

        $this->actingAs($doctor);

        // Panel URL for our own tenant, but a foreign record: scope 404s it.
        $this->get(PatientResource::getUrl('view', ['record' => $foreignPatient]))
            ->assertNotFound();

        $this->get(route('patients.summary-pdf', $foreignPatient))
            ->assertNotFound();
    }

    public function test_users_list_shows_only_own_clinic(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $otherClinic = Clinic::factory()->create();
        $foreignUser = User::factory()->create([
            'clinic_id' => $otherClinic->id,
            'role'      => UserRole::Doctor,
        ]);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin])
            ->assertCanNotSeeTableRecords([$foreignUser]);
    }

    public function test_mrn_is_unique_per_clinic_but_reusable_across_clinics(): void
    {
        Patient::factory()->create(['mrn' => 'MRN-1']);

        $otherClinic = Clinic::factory()->create();
        Patient::factory()->create(['mrn' => 'MRN-1', 'clinic_id' => $otherClinic->id]);

        $this->assertSame(2, Patient::withoutGlobalScope('clinic')->where('mrn', 'MRN-1')->count());

        $this->expectException(\Illuminate\Database\QueryException::class);
        Patient::factory()->create(['mrn' => 'MRN-1']);
    }

    public function test_daily_digest_is_isolated_per_clinic(): void
    {
        $doctorA = User::factory()->create(['role' => UserRole::Doctor]);

        $otherClinic = Clinic::factory()->create();
        $doctorB = User::factory()->create([
            'clinic_id' => $otherClinic->id,
            'role'      => UserRole::Doctor,
        ]);

        // Due follow-up only in clinic A (the test tenant).
        ClinicVisit::factory()->create(['next_follow_up_date' => today()->subDays(3)]);

        $this->artisan('pccekau:daily-digest')->assertSuccessful();

        $this->assertSame(1, $doctorA->notifications()->count());
        $this->assertSame(0, $doctorB->notifications()->count());
    }
}
