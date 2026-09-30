<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\PatientResource\Pages\CreatePatient;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Clinic;
use App\Models\ClinicVisit;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_clinic_command_creates_a_clinic_with_its_admin(): void
    {
        $this->artisan('pccekau:create-clinic')
            ->expectsQuestion('Clinic / hospital name', 'Riyadh Heart Center')
            ->expectsQuestion('Admin full name', 'Dr. New Founder')
            ->expectsQuestion('Admin email', 'founder@example.com')
            ->expectsQuestion('Admin password (min 12 characters, letters and numbers)', 'super-secret-12')
            ->assertSuccessful();

        $clinic = Clinic::where('slug', 'riyadh-heart-center')->first();
        $this->assertNotNull($clinic);

        $user = User::where('email', 'founder@example.com')->first();
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->clinic->is($clinic));
        $this->assertSame($user->id, $clinic->created_by);
    }

    public function test_create_clinic_command_resolves_slug_collisions(): void
    {
        Clinic::factory()->create(['slug' => 'heart-center']);

        $this->artisan('pccekau:create-clinic')
            ->expectsQuestion('Clinic / hospital name', 'Heart Center')
            ->expectsQuestion('Admin full name', 'Second Founder')
            ->expectsQuestion('Admin email', 'second@example.com')
            ->expectsQuestion('Admin password (min 12 characters, letters and numbers)', 'super-secret-12')
            ->assertSuccessful();

        $this->assertSame(
            'heart-center-2',
            User::where('email', 'second@example.com')->first()->clinic->slug,
        );
    }

    public function test_create_clinic_command_rejects_weak_passwords(): void
    {
        $this->artisan('pccekau:create-clinic')
            ->expectsQuestion('Clinic / hospital name', 'Weak Clinic')
            ->expectsQuestion('Admin full name', 'Someone')
            ->expectsQuestion('Admin email', 'weak@example.com')
            ->expectsQuestion('Admin password (min 12 characters, letters and numbers)', 'short1')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
        $this->assertDatabaseMissing('clinics', ['name' => 'Weak Clinic']);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->assertFalse(Route::has('filament.admin.auth.register'));
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
            'role' => UserRole::Doctor,
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

        $this->expectException(QueryException::class);
        Patient::factory()->create(['mrn' => 'MRN-1']);
    }

    // Regression: Filament associates records created through resource
    // create pages via Clinic relationships (e.g. Clinic::patients()) —
    // factories and relation managers don't exercise that path.
    public function test_resource_create_pages_associate_the_tenant(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        Livewire::actingAs($doctor)
            ->test(CreatePatient::class)
            ->fillForm([
                'mrn' => 'TEN-1',
                'name' => 'Tenant Association Check',
                'date_of_birth' => now()->subYears(3)->toDateString(),
                'gender' => 'male',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(
            $this->clinic->id,
            Patient::where('mrn', 'TEN-1')->first()->clinic_id,
        );
    }

    public function test_daily_digest_is_isolated_per_clinic(): void
    {
        $doctorA = User::factory()->create(['role' => UserRole::Doctor]);

        $otherClinic = Clinic::factory()->create();
        $doctorB = User::factory()->create([
            'clinic_id' => $otherClinic->id,
            'role' => UserRole::Doctor,
        ]);

        // Due follow-up only in clinic A (the test tenant).
        ClinicVisit::factory()->create(['next_follow_up_date' => today()->subDays(3)]);

        $this->artisan('pccekau:daily-digest')->assertSuccessful();

        $this->assertSame(1, $doctorA->notifications()->count());
        $this->assertSame(0, $doctorB->notifications()->count());
    }
}
