<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\PatientResource\Pages\ViewPatient;
use App\Filament\Resources\PatientResource\RelationManagers\AdmissionsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ClinicVisitsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\EpStudiesRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ImagingReportsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\InterventionsRelationManager;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
            ->get(\App\Filament\Resources\PatientResource::getUrl('view', ['record' => $patient]))
            ->assertOk()
            ->assertSee('Chart Test Child')
            ->assertSee('424242')
            ->assertSee('5 yr 3 mo')
            ->assertSee('Imaging Reports')
            ->assertSee('Interventions')
            ->assertSee('Clinic Visits');
    }

    // Regression: a missing form-component import only blows up when the
    // create modal renders, which page-load tests never exercise.
    public function test_relation_manager_create_modals_open(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        $managers = [
            AdmissionsRelationManager::class,
            ClinicVisitsRelationManager::class,
            ImagingReportsRelationManager::class,
            EpStudiesRelationManager::class,
            InterventionsRelationManager::class,
            DocumentsRelationManager::class,
        ];

        foreach ($managers as $manager) {
            Livewire::actingAs($doctor)
                ->test($manager, [
                    'ownerRecord' => $patient,
                    'pageClass' => ViewPatient::class,
                ])
                ->mountTableAction('create')
                ->assertSuccessful();
        }
    }
}
