<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\PatientResource\Pages\ViewPatient;
use App\Filament\Resources\PatientResource\RelationManagers\AdmissionsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ApprovalRequestsRelationManager;
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

    // Chart create buttons are links to the full-page forms (new tab, patient
    // preselected) so composing never blocks the rest of the app.
    public function test_relation_manager_create_actions_link_to_full_pages(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        $managers = [
            AdmissionsRelationManager::class      => \App\Filament\Resources\AdmissionResource::class,
            ApprovalRequestsRelationManager::class => \App\Filament\Resources\ApprovalRequestResource::class,
            ClinicVisitsRelationManager::class    => \App\Filament\Resources\ClinicVisitResource::class,
            ImagingReportsRelationManager::class  => \App\Filament\Resources\ImagingReportResource::class,
            EpStudiesRelationManager::class       => \App\Filament\Resources\EpStudyResource::class,
            InterventionsRelationManager::class   => \App\Filament\Resources\InterventionResource::class,
            DocumentsRelationManager::class       => \App\Filament\Resources\PatientDocumentResource::class,
        ];

        foreach ($managers as $manager => $resource) {
            Livewire::actingAs($doctor)
                ->test($manager, [
                    'ownerRecord' => $patient,
                    'pageClass' => ViewPatient::class,
                ])
                ->assertSuccessful()
                ->assertTableActionHasUrl('create', $resource::getUrl('create', ['patient_id' => $patient->id]));
        }
    }

    public function test_create_pages_prefill_the_patient_from_the_query_string(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)
            ->get(\App\Filament\Resources\ClinicVisitResource::getUrl('create', ['patient_id' => $patient->id]))
            ->assertOk()
            ->assertSee($patient->name);

        $this->actingAs($doctor)
            ->get(\App\Filament\Resources\AdmissionResource::getUrl('create', ['patient_id' => $patient->id]))
            ->assertOk()
            ->assertSee($patient->name);
    }
}
