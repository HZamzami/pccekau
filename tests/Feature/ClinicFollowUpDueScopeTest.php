<?php

namespace Tests\Feature;

use App\Enums\PatientStatus;
use App\Enums\UserRole;
use App\Models\ClinicVisit;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicFollowUpDueScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_follow_ups_include_active_patients_only(): void
    {
        $active = ClinicVisit::factory()
            ->for(Patient::factory()->create(['status' => PatientStatus::Active]))
            ->create(['next_follow_up_date' => today()->subDay()]);

        ClinicVisit::factory()
            ->for(Patient::factory()->create(['status' => PatientStatus::Deceased]))
            ->create(['next_follow_up_date' => today()->subDay()]);

        ClinicVisit::factory()
            ->for(Patient::factory()->create(['status' => PatientStatus::Transferred]))
            ->create(['next_follow_up_date' => today()->subDay()]);

        $due = ClinicVisit::dueFollowUps()->pluck('id');

        $this->assertCount(1, $due);
        $this->assertTrue($due->contains($active->id));
    }

    public function test_digest_skips_when_only_ineligible_patients_are_due(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        ClinicVisit::factory()
            ->for(Patient::factory()->create(['status' => PatientStatus::Deceased]))
            ->create(['next_follow_up_date' => today()->subDay()]);

        $this->artisan('pccekau:daily-digest')->assertSuccessful();

        $this->assertSame(0, $doctor->notifications()->count());
    }
}
