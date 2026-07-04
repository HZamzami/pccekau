<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientSummaryPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_gets_a_pdf(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $patient = Patient::factory()->create(['lesions' => ['tof']]);

        $this->actingAs($viewer)
            ->get(route('patients.summary-pdf', $patient))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_guest_is_redirected(): void
    {
        $patient = Patient::factory()->create();

        $this->get(route('patients.summary-pdf', $patient))->assertRedirect();
    }
}
