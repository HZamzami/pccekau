<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewPatientPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_shows_summary_and_relation_managers(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create([
            'name' => 'Chart Test Child',
            'mrn' => '424242',
            'date_of_birth' => now()->subYears(5)->subMonths(3),
        ]);

        $this->actingAs($doctor)
            ->get("/admin/patients/{$patient->id}")
            ->assertOk()
            ->assertSee('Chart Test Child')
            ->assertSee('424242')
            ->assertSee('5 yr 3 mo')
            ->assertSee('Imaging Reports')
            ->assertSee('Interventions')
            ->assertSee('Clinic Visits');
    }
}
