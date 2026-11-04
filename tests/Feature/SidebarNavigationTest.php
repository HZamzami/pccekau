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
                'Booking', 'Central Booking', 'All Bookings', 'Wait-list', 'Procedure Calendar',
                'Reports & Documents', 'Procedures', 'Electrophysiology', 'Imaging Reports', 'Patient Documents',
                'Schedules', 'Master Schedule', 'Staff', 'Weekly Coverage', 'Fellows Rotation', 'Consultant Schedule',
                'Admin', 'Approval Requests', 'Users', 'Activity Log',
            ]);
    }

    public function test_booking_sidebar_has_central_booking_and_all_bookings(): void
    {
        $navUrls = fn () => collect(ProcedureBookingResource::getNavigationItems())
            ->mapWithKeys(fn ($item) => [$item->getLabel() => $item->getUrl()])
            ->all();

        $this->actingAs(User::factory()->create(['role' => UserRole::Doctor]));
        $this->assertSame([
            'Central Booking' => ProcedureBookingResource::getUrl('create'),
            'All Bookings' => ProcedureBookingResource::getUrl('index'),
        ], $navUrls());
        $this->get(ProcedureBookingResource::getUrl('create'))->assertOk()->assertSeeText('Create Booking');
        $this->get(ProcedureBookingResource::getUrl('index'))->assertOk()->assertSeeText('All Bookings');

        // Read-only users can't book, so they only get the list.
        $this->actingAs(User::factory()->create(['role' => UserRole::Viewer]));
        $this->assertSame(['All Bookings' => ProcedureBookingResource::getUrl('index')], $navUrls());
    }

    public function test_procedures_list_filters_by_category_chips(): void
    {
        $surgery = Intervention::factory()->for($this->patient)->create(['type' => InterventionType::Surgery]);
        $cath = Intervention::factory()->for($this->patient)->create(['type' => InterventionType::DiagnosticCath]);
        $ablation = Intervention::factory()->for($this->patient)->create(['type' => InterventionType::Ablation]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        Livewire::actingAs($doctor)->test(ListInterventions::class)
            ->assertCanSeeTableRecords([$surgery, $cath, $ablation])
            ->filterTable('category', ['surgery'])
            ->assertCanSeeTableRecords([$surgery])
            ->assertCanNotSeeTableRecords([$cath, $ablation])
            ->filterTable('category', ['surgery', 'cath'])
            ->assertCanSeeTableRecords([$surgery, $cath])
            ->assertCanNotSeeTableRecords([$ablation]);

        // The EP page's "Invasive EP procedures" link opens the list pre-filtered.
        Livewire::withQueryParams(['tableFilters' => ['category' => ['values' => ['ep']]]])->actingAs($doctor)->test(ListInterventions::class)
            ->assertCanSeeTableRecords([$ablation])
            ->assertCanNotSeeTableRecords([$surgery, $cath]);

        // Adding from the Surgery page pre-fills the type; Cath has several, so it doesn't guess
        Livewire::withQueryParams(['category' => 'surgery'])->actingAs($doctor)->test(CreateIntervention::class)
            ->assertFormSet(['type' => InterventionType::Surgery->value]);

        Livewire::withQueryParams(['category' => 'cath'])->actingAs($doctor)->test(CreateIntervention::class)
            ->assertFormSet(['type' => null]);
    }

    public function test_imaging_reports_filter_by_modality_chips(): void
    {
        $fetal = ImagingReport::factory()->for($this->patient)->create(['type' => ImagingType::FetalEcho]);
        $tee = ImagingReport::factory()->for($this->patient)->create(['type' => ImagingType::EchoTee]);
        $mri = ImagingReport::factory()->for($this->patient)->create(['type' => ImagingType::Mri]);
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        Livewire::actingAs($doctor)->test(ListImagingReports::class)
            ->set('activeTab', 'all')
            ->filterTable('modality', ['fetal_echo'])
            ->assertCanSeeTableRecords([$fetal])
            ->assertCanNotSeeTableRecords([$tee, $mri])
            ->filterTable('modality', ['echo', 'mri'])
            ->assertCanSeeTableRecords([$tee, $mri])
            ->assertCanNotSeeTableRecords([$fetal]);

        // The modalities are chips now, not sidebar sub-items.
        $this->actingAs($doctor)
            ->get(ImagingReportResource::getUrl('index'))
            ->assertOk()
            ->assertSee('pc-chip')
            ->assertSeeTextInOrder(['Modality', 'Echo', 'Fetal Echo', 'MRI', 'CT', 'Type']);
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
