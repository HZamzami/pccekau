<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ClinicVisit;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_read_but_not_write(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $patient = Patient::factory()->create();

        $this->assertTrue($viewer->can('view', $patient));
        $this->assertFalse($viewer->can('create', Patient::class));
        $this->assertFalse($viewer->can('update', $patient));
        $this->assertFalse($viewer->can('delete', $patient));
        $this->assertFalse($viewer->can('create', ClinicVisit::class));
    }

    public function test_doctor_can_write_but_not_delete(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        $this->assertTrue($doctor->can('create', Patient::class));
        $this->assertTrue($doctor->can('update', $patient));
        $this->assertFalse($doctor->can('delete', $patient));
    }

    public function test_only_admin_can_delete_and_manage_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        $this->assertTrue($admin->can('delete', $patient));
        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertFalse($doctor->can('viewAny', User::class));
    }
}
