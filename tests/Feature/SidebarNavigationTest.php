<?php

namespace Tests\Feature;

use App\Enums\ImagingType;
use App\Enums\InterventionType;
use App\Enums\ProcedureCategory;
use App\Enums\UserRole;
use App\Enums\WaitlistStatus;
use App\Filament\Resources\ImagingReportResource;
use App\Filament\Resources\ImagingReportResource\Pages\ListImagingReports;
use App\Filament\Resources\InterventionResource\Pages\CreateIntervention;
use App\Filament\Resources\InterventionResource\Pages\ListInterventions;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Models\ImagingReport;
use App\Models\Intervention;
use App\Models\Patient;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::factory()->create();
    }

    public function test_sidebar_lists_groups_and_items_in_order(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(PatientResource::getUrl('index'))
            ->assertOk()
            ->assertSeeTextInOrder([
                'Dashboard',
                'Patients', 'Patient List',
                'Clinical Care', 'Clinic Visits', 'Clinic Follow-Ups', 'Inpatients', 'Handover Board', 'Case Discussions',
                'Booking', 'Central Booking', 'Wait-list', 'Procedure Calendar',
                'Reports & Documents', 'Surgery', 'Cath', 'Electrophysiology', 'Imaging Reports', 'Patient Documents',
                'Schedules', 'Master Schedule', 'Staff', 'Weekly Coverage', 'Fellows Rotation', 'Consultant Schedule',
                'Admin', 'Approval Requests', 'Users', 'Activity Log',
            ]);
    }

    public function test_central_booking_opens_the_booking_form_for_writers_and_the_list_for_viewers(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Doctor]));
        $this->assertSame(ProcedureBookingResource::getUrl('create'), ProcedureBookingResource::getNavigationUrl());
        $this->get(ProcedureBookingResource::getUrl('create'))->assertOk()->assertSeeText('Create Booking');

        $this->actingAs(User::factory()->create(['role' => UserRole::Viewer]));
        $this->assertSame(ProcedureBookingResource::getUrl('index'), ProcedureBookingResource::getNavigationUrl());
    }

    public function test_surgery_and_cath_pages_list_only_their_procedures(): void
    {
        $surgery = Intervention::factory()->for($this->patient)->create(['type' => InterventionType::Surgery]);
        $cath = Intervention::factory()->for($this->patient)->create(['type' => InterventionType::DiagnosticCath]);
        $ablation = Intervention::factory()->for($this->patient)->create(['type' => InterventionType::Ablation]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        Livewire::withQueryParams(['category' => 'surgery'])->actingAs($doctor)->test(ListInterventions::class)
            ->assertSee('Surgery')
            ->assertCanSeeTableRecords([$surgery])
            ->assertCanNotSeeTableRecords([$cath, $ablation]);

        Livewire::withQueryParams(['category' => 'cath'])->actingAs($doctor)->test(ListInterventions::class)
            ->assertCanSeeTableRecords([$cath])
            ->assertCanNotSeeTableRecords([$surgery, $ablation]);

        Livewire::withQueryParams(['category' => 'ep'])->actingAs($doctor)->test(ListInterventions::class)
            ->assertCanSeeTableRecords([$ablation])
            ->assertCanNotSeeTableRecords([$surgery, $cath]);

        Livewire::withQueryParams([])->actingAs($doctor)->test(ListInterventions::class)
            ->assertSee('All procedures')
            ->assertCanSeeTableRecords([$surgery, $cath, $ablation]);

        // Adding from the Surgery page pre-fills the type; Cath has several, so it doesn't guess
        Livewire::withQueryParams(['category' => 'surgery'])->actingAs($doctor)->test(CreateIntervention::class)
            ->assertFormSet(['type' => InterventionType::Surgery->value]);

        Livewire::withQueryParams(['category' => 'cath'])->actingAs($doctor)->test(CreateIntervention::class)
            ->assertFormSet(['type' => null]);
    }

    public function test_imaging_modality_pages_filter_and_unfold_under_imaging_reports(): void
    {
        $fetal = ImagingReport::factory()->for($this->patient)->create(['type' => ImagingType::FetalEcho]);
        $tee = ImagingReport::factory()->for($this->patient)->create(['type' => ImagingType::EchoTee]);
        $mri = ImagingReport::factory()->for($this->patient)->create(['type' => ImagingType::Mri]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        Livewire::withQueryParams(['modality' => 'fetal_echo'])->actingAs($doctor)->test(ListImagingReports::class)
            ->set('activeTab', 'all')
            ->assertSee('Fetal Echo Reports')
            ->assertCanSeeTableRecords([$fetal])
            ->assertCanNotSeeTableRecords([$tee, $mri]);

        Livewire::withQueryParams(['modality' => 'echo'])->actingAs($doctor)->test(ListImagingReports::class)
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$tee])
            ->assertCanNotSeeTableRecords([$fetal, $mri]);

        $this->actingAs($doctor)
            ->get(ImagingReportResource::getUrl('index', ['modality' => 'fetal_echo']))
            ->assertOk()
            ->assertSeeTextInOrder(['Imaging Reports', 'Echo', 'Fetal Echo', 'MRI', 'CT', 'Patient Documents']);
    }

    public function test_fetal_echo_can_be_ordered(): void
    {
        $this->assertSame(ImagingType::FetalEcho, ProcedureCategory::FetalEcho->imagingType());
        $this->assertSame(['echo'], ProcedureCategory::FetalEcho->slotTypes());
    }

    public function test_waitlist_shows_only_patients_still_waiting(): void
    {
        $waiting = WaitlistEntry::create(['patient_id' => $this->patient->id, 'category' => ProcedureCategory::Cath]);
        $scheduled = WaitlistEntry::create(['patient_id' => $this->patient->id, 'category' => ProcedureCategory::Mri, 'status' => WaitlistStatus::Scheduled]);
        $removed = WaitlistEntry::create(['patient_id' => $this->patient->id, 'category' => ProcedureCategory::Ct, 'status' => WaitlistStatus::Removed, 'removed_reason' => 'Done elsewhere']);

        Livewire::actingAs(User::factory()->create(['role' => UserRole::Doctor]))
            ->test(ListWaitlistEntries::class)
            ->assertCanSeeTableRecords([$waiting])
            ->assertCanNotSeeTableRecords([$scheduled, $removed]);
    }
}
