<?php

namespace Tests\Feature;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Enums\WaitlistStatus;
use App\Filament\Pages\ProcedureCalendarPage;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\PatientResource\Pages\ViewPatient;
use App\Filament\Resources\PatientResource\RelationManagers\ProcedureBookingsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\WaitlistEntriesRelationManager;
use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Models\Patient;
use App\Models\ProcedureBooking;
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

    public function test_bookings_and_waitlist_show_on_the_patient_chart(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();
        $other = Patient::factory()->create();

        $mine = $this->booking($patient, ['procedure' => 'PDA device closure']);
        $theirs = $this->booking($other, ['slot_number' => 2, 'procedure' => 'Someone else']);
        $entry = WaitlistEntry::create([
            'patient_id' => $patient->id,
            'category' => ProcedureCategory::Mri,
            'procedure' => 'Cardiac MRI',
        ]);

        $this->actingAs($doctor)
            ->get(PatientResource::getUrl('view', ['record' => $patient]))
            ->assertOk()
            ->assertSee('Procedure Bookings')
            ->assertSee('Wait-list');

        Livewire::actingAs($doctor)
            ->test(ProcedureBookingsRelationManager::class, ['ownerRecord' => $patient, 'pageClass' => ViewPatient::class])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs])
            ->assertTableActionHasUrl('create', ProcedureBookingResource::getUrl('create', ['patient_id' => $patient->id]));

        Livewire::actingAs($doctor)
            ->test(WaitlistEntriesRelationManager::class, ['ownerRecord' => $patient, 'pageClass' => ViewPatient::class])
            ->assertCanSeeTableRecords([$entry])
            ->assertTableActionHasUrl('create', WaitlistEntryResource::getUrl('create', ['patient_id' => $patient->id]));
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

        $this->assertSame(['mri_ct_1'], array_keys($page->instance()->getVisibleColumns()));
    }
}
