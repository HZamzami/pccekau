<?php

namespace Tests\Feature;

use App\Enums\EpStudyType;
use App\Enums\ImagingType;
use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Enums\WaitlistStatus;
use App\Filament\Pages\ProcedureCalendarPage;
use App\Filament\Pages\SchedulesPage;
use App\Filament\Resources\ConsultantScheduleResource;
use App\Filament\Resources\EpStudyResource\Pages\ListEpStudies;
use App\Filament\Resources\ImagingReportResource\Pages\ListImagingReports;
use App\Filament\Resources\InterventionResource;
use App\Filament\Resources\InterventionResource\Pages\ListInterventions;
use App\Filament\Resources\MdtDiscussionResource\Pages\ListMdtDiscussions;
use App\Filament\Resources\OncallScheduleResource;
use App\Filament\Resources\PatientDocumentResource;
use App\Filament\Resources\PatientResource\Pages\ViewPatient;
use App\Filament\Resources\PatientResource\RelationManagers\WaitlistEntriesRelationManager;
use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\ProcedureBookingResource\Pages\CreateProcedureBooking;
use App\Filament\Resources\StaffResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Filament\Resources\WaitlistEntryResource\Pages\CreateWaitlistEntry;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ProcedureOrderingTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $this->patient = Patient::factory()->create();
    }

    private function book(array $form): Testable
    {
        return Livewire::actingAs($this->doctor)
            ->test(CreateProcedureBooking::class)
            ->fillForm([
                'patient_id' => $this->patient->id,
                'booking_date' => now()->addWeek()->toDateString(),
                'procedure_status' => ProcedureStatus::Ordered->value,
                ...$form,
            ])
            ->call('create');
    }

    public function test_3d_echo_goes_to_imaging(): void
    {
        $this->book(['category' => 'echo_3d', 'slot_type' => 'echo'])->assertHasNoFormErrors();

        $this->assertSame(ImagingType::Echo3d, $this->patient->imagingReports()->sole()->type);
    }

    public function test_holter_goes_to_electrophysiology_without_a_slot(): void
    {
        $this->book(['category' => 'holter'])->assertHasNoFormErrors();

        $booking = ProcedureBooking::sole();
        $this->assertNull($booking->slot_type);
        $this->assertSame(EpStudyType::Holter, $this->patient->epStudies()->sole()->type);

        $this->patient->epStudies()->sole()->update(['procedure_status' => ProcedureStatus::Done]);
        $this->assertSame(ProcedureStatus::Done, $booking->fresh()->procedure_status);
    }

    public function test_case_discussion_goes_to_case_discussions_and_needs_a_reason(): void
    {
        $this->book(['category' => 'case_discussion'])->assertHasFormErrors(['procedure', 'diagnosis']);

        $this->book([
            'category' => 'case_discussion',
            'procedure' => 'Timing of Glenn',
            'diagnosis' => 'HLHS s/p Norwood',
        ])->assertHasNoFormErrors();

        $discussion = $this->patient->mdtDiscussions()->sole();
        $this->assertSame('Timing of Glenn', $discussion->reason_for_discussion);
        $this->assertSame('HLHS s/p Norwood', $discussion->diagnosis);
        $this->assertSame(now()->addWeek()->toDateString(), $discussion->discussion_date->toDateString());
        $this->assertFalse((bool) $discussion->discussed);

        $discussion->update(['discussed' => true]);
        $this->assertSame(ProcedureStatus::Done, ProcedureBooking::sole()->procedure_status);
    }

    public function test_slot_number_is_optional_and_unslotted_bookings_show_in_the_no_slot_column(): void
    {
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $this->book(['category' => 'mri', 'slot_type' => 'mri_ct', 'booking_date' => $sunday->copy()->addDay()->toDateString()])->assertHasNoFormErrors();
        $this->book(['category' => 'ecg', 'booking_date' => $sunday->copy()->addDay()->toDateString()])->assertHasNoFormErrors();

        $monday = Livewire::actingAs($this->doctor)->test(ProcedureCalendarPage::class)->instance()->getGrid()[1];

        $this->assertCount(2, $monday['cells']['no_slot']);
        $this->assertCount(0, $monday['cells']['mri_ct_1']);
    }

    public function test_the_booking_form_can_add_to_the_waitlist_instead(): void
    {
        $this->book(['mode' => 'waitlist', 'category' => 'cath', 'procedure' => 'PDA closure', 'priority' => 'urgent'])
            ->assertHasNoFormErrors()
            ->assertRedirect(WaitlistEntryResource::getUrl('index'));

        $entry = WaitlistEntry::sole();
        $this->assertSame(ProcedureCategory::Cath, $entry->category);
        $this->assertSame('PDA closure', $entry->procedure);
        $this->assertSame(0, ProcedureBooking::count());
        $this->assertSame(0, $this->patient->interventions()->count());
    }

    public function test_scheduling_a_waitlisted_case_discussion_puts_it_on_the_calendar_and_its_tab(): void
    {
        $entry = WaitlistEntry::create([
            'patient_id' => $this->patient->id,
            'category' => ProcedureCategory::CaseDiscussion,
            'procedure' => 'Surgical options',
            'diagnosis' => 'TOF',
        ]);

        Livewire::actingAs($this->doctor)
            ->test(ListWaitlistEntries::class)
            ->callTableAction('schedule', $entry->fresh(), ['booking_date' => now()->addDays(3)->toDateString()])
            ->assertHasNoTableActionErrors();

        $this->assertSame(WaitlistStatus::Scheduled, $entry->fresh()->status);
        $this->assertNull(ProcedureBooking::sole()->slot_type);
        $this->assertSame('Surgical options', $this->patient->mdtDiscussions()->sole()->reason_for_discussion);
    }

    public function test_patient_page_waitlist_tab_lists_and_schedules(): void
    {
        $entry = WaitlistEntry::create(['patient_id' => $this->patient->id, 'category' => ProcedureCategory::Holter]);

        Livewire::actingAs($this->doctor)
            ->test(WaitlistEntriesRelationManager::class, ['ownerRecord' => $this->patient, 'pageClass' => ViewPatient::class])
            ->assertCanSeeTableRecords([$entry])
            ->assertTableActionHasUrl('create', WaitlistEntryResource::getUrl('create', ['patient_id' => $this->patient->id]))
            ->callTableAction('schedule', $entry->fresh(), ['booking_date' => now()->addDay()->toDateString()])
            ->assertHasNoTableActionErrors();

        $this->assertSame(EpStudyType::Holter, $this->patient->epStudies()->sole()->type);
    }

    public function test_list_pages_have_an_add_to_waitlist_button_for_their_kind(): void
    {
        $pages = [
            ListEpStudies::class => 'ep',
            ListInterventions::class => 'interventions',
            ListImagingReports::class => 'imaging',
            ListMdtDiscussions::class => 'case_discussion',
        ];

        foreach ($pages as $page => $group) {
            Livewire::actingAs($this->doctor)
                ->test($page)
                ->assertActionHasUrl('addToWaitlist', WaitlistEntryResource::getUrl('create', ['group' => $group]));
        }

        $this->actingAs($this->doctor)
            ->get(WaitlistEntryResource::getUrl('create', ['group' => 'imaging']))
            ->assertOk()
            ->assertSee('3D Echo')
            ->assertDontSee('Holter');
    }

    public function test_waitlist_create_page_is_limited_to_its_group(): void
    {
        Livewire::actingAs($this->doctor)
            ->withQueryParams(['group' => 'ep'])
            ->test(CreateWaitlistEntry::class)
            ->fillForm(['patient_id' => $this->patient->id, 'category' => 'mri'])
            ->call('create')
            ->assertHasFormErrors(['category']);
    }

    public function test_sidebar_and_tab_order(): void
    {
        $this->assertSame(
            [8, 9, 10, 11, 12],
            [
                InterventionResource::getNavigationSort(),
                WaitlistEntryResource::getNavigationSort(),
                ProcedureBookingResource::getNavigationSort(),
                ProcedureCalendarPage::getNavigationSort(),
                PatientDocumentResource::getNavigationSort(),
            ],
        );

        $this->assertLessThan(StaffResource::getNavigationSort(), SchedulesPage::getNavigationSort());
        $this->assertLessThan(OncallScheduleResource::getNavigationSort(), SchedulesPage::getNavigationSort());
        $this->assertLessThan(ConsultantScheduleResource::getNavigationSort(), SchedulesPage::getNavigationSort());

        $html = Livewire::actingAs(User::factory()->create(['role' => UserRole::Admin]))->test(SchedulesPage::class)->html();
        $this->assertGreaterThan(strpos($html, 'Consultants'), strpos($html, 'Fellows Rotation'));
    }

    public function test_admission_stay_reads_as_days_and_hours(): void
    {
        $admission = Admission::factory()->for($this->patient)->create(['admitted_at' => now(), 'discharged_at' => null]);

        $this->travel(30)->minutes();
        $this->assertSame('<1h', $admission->stayLabel());

        $this->travel(5)->hours();
        $this->assertSame('5h', $admission->stayLabel());

        $this->travel(2)->days();
        $this->assertSame('2d 5h', $admission->stayLabel());

        $admission->update(['discharged_at' => $admission->admitted_at->copy()->addDays(3)]);
        $this->assertSame('3d', $admission->stayLabel());
    }
}
