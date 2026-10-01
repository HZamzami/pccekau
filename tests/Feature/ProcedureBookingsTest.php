<?php

namespace Tests\Feature;

use App\Enums\ImagingType;
use App\Enums\InterventionType;
use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Enums\WaitlistStatus;
use App\Filament\Pages\ProcedureCalendarPage;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\PatientResource\Pages\ViewPatient;
use App\Filament\Resources\PatientResource\RelationManagers\InterventionsRelationManager;
use App\Filament\Resources\ProcedureBookingResource\Pages\CreateProcedureBooking;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use App\Models\Staff;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProcedureBookingsTest extends TestCase
{
    use RefreshDatabase;

    private function booking(Patient $patient, array $attributes = []): ProcedureBooking
    {
        return ProcedureBooking::create([
            'patient_id' => $patient->id,
            'booking_date' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDay(),
            'slot_type' => 'cath_day_care',
            'slot_number' => 1,
            'category' => ProcedureCategory::Cath,
            'procedure' => 'Diagnostic cath',
            'procedure_status' => ProcedureStatus::Confirmed,
            ...$attributes,
        ]);
    }

    public function test_a_cath_booking_lands_on_the_interventions_tab_not_its_own_tab(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();
        $staff = Staff::factory()->create();

        Livewire::actingAs($doctor)
            ->test(CreateProcedureBooking::class)
            ->fillForm([
                'patient_id' => $patient->id,
                'booking_date' => now()->addWeek()->toDateString(),
                'category' => 'cath',
                'intervention_type' => InterventionType::InterventionalCathDevice->value,
                'slot_type' => 'cath_day_care',
                'slot_number' => 1,
                'staff_id' => $staff->id,
                'procedure' => 'PDA device closure',
                'procedure_status' => ProcedureStatus::Ordered->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $intervention = $patient->interventions()->sole();
        $this->assertSame(InterventionType::InterventionalCathDevice, $intervention->type);
        $this->assertSame('PDA device closure', $intervention->name);
        $this->assertSame(ProcedureStatus::Ordered, $intervention->procedure_status);
        $this->assertSame(now()->addWeek()->toDateString(), $intervention->date->toDateString());
        $this->assertSame($staff->id, $intervention->operator_id);
        $this->assertSame(0, $patient->imagingReports()->count());

        Livewire::actingAs($doctor)
            ->test(InterventionsRelationManager::class, ['ownerRecord' => $patient, 'pageClass' => ViewPatient::class])
            ->assertCanSeeTableRecords([$intervention]);

        $tabs = array_map(fn ($manager) => $manager::getTitle($patient, ViewPatient::class), PatientResource::getRelations());
        $this->assertNotContains('Procedure Bookings', $tabs);
        $this->assertContains('Wait-list', $tabs);
    }

    public function test_cath_and_ep_bookings_must_say_the_exact_procedure(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        Livewire::actingAs($doctor)
            ->test(CreateProcedureBooking::class)
            ->fillForm([
                'patient_id' => Patient::factory()->create()->id,
                'booking_date' => now()->addWeek()->toDateString(),
                'category' => 'ep',
                'slot_type' => 'cath_day_care',
                'slot_number' => 1,
                'procedure_status' => ProcedureStatus::Ordered->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['intervention_type']);
    }

    public function test_imaging_bookings_land_on_the_imaging_tab(): void
    {
        $patient = Patient::factory()->create();
        $this->booking($patient, ['slot_type' => 'mri_ct', 'category' => ProcedureCategory::Mri]);
        $this->booking($patient, ['slot_type' => 'echo', 'category' => ProcedureCategory::Tee]);

        $this->assertEqualsCanonicalizing(
            [ImagingType::Mri, ImagingType::EchoTee],
            $patient->imagingReports()->get()->pluck('type')->all(),
        );
        $this->assertSame(0, $patient->interventions()->count());
    }

    public function test_other_bookings_stay_off_the_chart(): void
    {
        $patient = Patient::factory()->create();
        $this->booking($patient, ['slot_type' => 'echo', 'category' => ProcedureCategory::Other]);

        $this->assertSame(0, $patient->interventions()->count() + $patient->imagingReports()->count());
    }

    public function test_status_syncs_both_ways(): void
    {
        $patient = Patient::factory()->create();
        $booking = $this->booking($patient, ['intervention_type' => InterventionType::DiagnosticCath]);
        $intervention = $booking->fresh()->intervention;

        $booking->update(['procedure_status' => ProcedureStatus::Postponed]);
        $this->assertSame(ProcedureStatus::Postponed, $intervention->fresh()->procedure_status);

        $intervention->fresh()->update(['procedure_status' => ProcedureStatus::Done, 'date' => now()->subDay()]);
        $this->assertSame(ProcedureStatus::Done, $booking->fresh()->procedure_status);
        // The chart's own date edit must survive the status echo back to the booking.
        $this->assertSame(now()->subDay()->toDateString(), $intervention->fresh()->date->toDateString());
    }

    public function test_changing_category_moves_the_record_to_the_right_tab(): void
    {
        $patient = Patient::factory()->create();
        $booking = $this->booking($patient);

        $booking->update(['category' => ProcedureCategory::Ct, 'slot_type' => 'mri_ct']);

        $this->assertSame(0, $patient->interventions()->count());
        $this->assertSame(ImagingType::Ct, $patient->imagingReports()->sole()->type);
    }

    public function test_deleting_a_booking_removes_its_pending_chart_record_but_keeps_done_ones(): void
    {
        $patient = Patient::factory()->create();
        $pending = $this->booking($patient);
        $done = $this->booking($patient, ['slot_number' => 2, 'procedure_status' => ProcedureStatus::Done]);

        $pending->delete();
        $done->delete();

        $this->assertSame(ProcedureStatus::Done, $patient->interventions()->sole()->procedure_status);
    }

    public function test_scheduling_a_waitlisted_cath_puts_it_on_the_interventions_tab(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();
        $entry = WaitlistEntry::create(['patient_id' => $patient->id, 'category' => ProcedureCategory::Cath]);

        Livewire::actingAs($doctor)
            ->test(ListWaitlistEntries::class)
            ->callTableAction('schedule', $entry->fresh(), [
                'intervention_type' => InterventionType::InterventionalCathBalloon->value,
                'booking_date' => now()->addWeek()->toDateString(),
                'slot_type' => 'cath_day_care',
                'slot_number' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $intervention = $patient->interventions()->sole();
        $this->assertSame(InterventionType::InterventionalCathBalloon, $intervention->type);
        $this->assertSame(ProcedureStatus::Confirmed, $intervention->procedure_status);
    }

    public function test_bookings_cascade_with_patient_soft_delete(): void
    {
        $patient = Patient::factory()->create();
        $booking = $this->booking($patient);

        $patient->delete();
        $this->assertSoftDeleted('procedure_bookings', ['id' => $booking->id]);

        $patient->restore();
        $this->assertNull($booking->fresh()->deleted_at);
    }

    public function test_scheduling_from_the_waitlist_carries_the_category_and_picks_its_slot(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();
        $entry = WaitlistEntry::create([
            'patient_id' => $patient->id,
            'category' => ProcedureCategory::Surgery,
            'procedure' => 'Glenn',
        ]);

        Livewire::actingAs($doctor)
            ->test(ListWaitlistEntries::class)
            ->mountTableAction('schedule', $entry)
            ->assertTableActionDataSet(['slot_type' => 'or'])
            ->setTableActionData(['booking_date' => now()->addWeek()->toDateString(), 'slot_number' => 1])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $booking = $patient->procedureBookings()->first();
        $this->assertSame(ProcedureCategory::Surgery, $booking->category);
        $this->assertSame('or', $booking->slot_type);
        $this->assertSame(WaitlistStatus::Scheduled, $entry->fresh()->status);
    }

    public function test_calendar_filters_by_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $mriPatient = Patient::factory()->create(['name' => 'Mri Kid']);
        $ctPatient = Patient::factory()->create(['name' => 'Ct Kid']);
        $cathPatient = Patient::factory()->create(['name' => 'Cath Kid']);

        $monday = Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDay();
        $this->booking($mriPatient, ['slot_type' => 'mri_ct', 'category' => ProcedureCategory::Mri]);
        $this->booking($ctPatient, ['slot_type' => 'mri_ct', 'category' => ProcedureCategory::Ct, 'booking_date' => $monday->copy()->addDay()]);
        $this->booking($cathPatient);

        $page = Livewire::actingAs($admin)
            ->test(ProcedureCalendarPage::class)
            ->assertSee(['Mri Kid', 'Ct Kid', 'Cath Kid', 'Operating Room', 'Echo'])
            ->set('category', 'mri')
            ->assertSee('Mri Kid')
            ->assertDontSee(['Ct Kid', 'Cath Kid']);

        $this->assertSame(['mri_ct_1', 'no_slot'], array_keys($page->instance()->getVisibleColumns()));
    }
}
