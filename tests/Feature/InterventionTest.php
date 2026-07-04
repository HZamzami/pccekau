<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Intervention;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_interventions_cascade_with_patient_soft_delete(): void
    {
        $patient = Patient::factory()->create();
        $intervention = Intervention::factory()->for($patient)->create();

        $patient->delete();
        $this->assertSoftDeleted('interventions', ['id' => $intervention->id]);

        $patient->restore();
        $this->assertNull($intervention->fresh()->deleted_at);
    }

    public function test_policy_matrix(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $intervention = Intervention::factory()->create();

        $this->assertTrue($viewer->can('view', $intervention));
        $this->assertFalse($viewer->can('create', Intervention::class));
        $this->assertTrue($doctor->can('update', $intervention));
        $this->assertFalse($doctor->can('delete', $intervention));
    }
}
