<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ClinicVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_digest_notifies_doctors_and_admins_only(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        ClinicVisit::factory()->create(['next_follow_up_date' => today()->subDays(3)]);

        $this->artisan('pccekau:daily-digest')->assertSuccessful();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $doctor->notifications()->count());
        $this->assertSame(0, $viewer->notifications()->count());
    }

    public function test_digest_is_skipped_when_nothing_is_due(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        ClinicVisit::factory()->create(['next_follow_up_date' => today()->addWeek()]);

        $this->artisan('pccekau:daily-digest')->assertSuccessful();

        $this->assertSame(0, $doctor->notifications()->count());
    }
}
