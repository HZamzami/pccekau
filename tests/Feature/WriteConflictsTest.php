<?php

namespace Tests\Feature;

use App\Enums\InterventionType;
use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Enums\WaitlistStatus;
use App\Filament\Resources\OncallScheduleResource\Pages\CreateOncallSchedule;
use App\Filament\Resources\ProcedureBookingResource\Pages\CreateProcedureBooking;
use App\Filament\Resources\ProcedureBookingResource\Pages\EditProcedureBooking;
use App\Filament\Resources\StaffResource\Pages\CreateStaff;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Models\OncallSchedule;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use App\Models\Staff;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Saving something that clashes with an existing record must come back as a
// form error, never as a database exception (a 500 for the user).
class WriteConflictsTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $this->date = now()->addWeek()->toDateString();
    }

    private function book(array $attributes = []): ProcedureBooking
    {
        return ProcedureBooking::create([
            'patient_id' => Patient::factory()->create()->id,
            'booking_date' => $this->date,
            'slot_type' => 'cath_day_care',
            'slot_number' => 1,
            'category' => ProcedureCategory::Cath,
            'intervention_type' => InterventionType::DiagnosticCath,
            'procedure_status' => ProcedureStatus::Confirmed,
            ...$attributes,
        ]);
    }

    private function bookingForm(array $overrides = []): array
    {
        return [
            'patient_id' => Patient::factory()->create()->id,
            'booking_date' => $this->date,
            'category' => ProcedureCategory::Cath->value,
            'intervention_type' => InterventionType::DiagnosticCath->value,
            'slot_type' => 'cath_day_care',
            'slot_number' => 1,
            'procedure_status' => ProcedureStatus::Ordered->value,
            ...$overrides,
        ];
    }

    public function test_booking_a_taken_slot_is_a_form_error(): void
    {
        $this->book();

        Livewire::actingAs($this->doctor)
            ->test(CreateProcedureBooking::class)
            ->fillForm($this->bookingForm())
            ->call('create')
            ->assertHasFormErrors(['slot_number']);

        $this->assertSame(1, ProcedureBooking::count());
    }

    public function test_the_other_day_care_slot_is_still_bookable(): void
    {
        $this->book();

        Livewire::actingAs($this->doctor)
            ->test(CreateProcedureBooking::class)
            ->fillForm($this->bookingForm(['slot_number' => 2]))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_a_slot_freed_by_deleting_a_booking_can_be_rebooked(): void
    {
        $this->book()->delete();

        Livewire::actingAs($this->doctor)
            ->test(CreateProcedureBooking::class)
            ->fillForm($this->bookingForm())
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, ProcedureBooking::count());
    }

    public function test_editing_a_booking_without_moving_it_is_not_a_clash(): void
    {
        $booking = $this->book();

        Livewire::actingAs($this->doctor)
            ->test(EditProcedureBooking::class, ['record' => $booking->getRouteKey()])
            ->fillForm(['procedure' => 'Changed'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_moving_a_booking_into_a_taken_slot_is_a_form_error(): void
    {
        $this->book();
        $other = $this->book(['slot_number' => 2]);

        Livewire::actingAs($this->doctor)
            ->test(EditProcedureBooking::class, ['record' => $other->getRouteKey()])
            ->fillForm(['slot_number' => 1])
            ->call('save')
            ->assertHasFormErrors(['slot_number']);
    }

    public function test_slot_numbers_beyond_the_calendar_are_rejected(): void
    {
        Livewire::actingAs($this->doctor)
            ->test(CreateProcedureBooking::class)
            ->fillForm($this->bookingForm(['slot_type' => 'mri_ct', 'category' => 'mri', 'slot_number' => 2]))
            ->call('create')
            ->assertHasFormErrors(['slot_number']);
    }

    public function test_scheduling_from_the_waitlist_into_a_taken_slot_is_a_form_error(): void
    {
        $this->book();
        $entry = WaitlistEntry::create([
            'patient_id' => Patient::factory()->create()->id,
            'category' => ProcedureCategory::Cath,
        ]);

        Livewire::actingAs($this->doctor)
            ->test(ListWaitlistEntries::class)
            ->callTableAction('schedule', $entry->fresh(), ['booking_date' => $this->date, 'slot_type' => 'cath_day_care', 'slot_number' => 1])
            ->assertHasTableActionErrors(['slot_number']);

        $this->assertSame(WaitlistStatus::Waiting, $entry->fresh()->status);
        $this->assertSame(1, ProcedureBooking::count());
    }

    public function test_linking_one_login_to_two_staff_is_a_form_error(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $login = User::factory()->create();
        Staff::factory()->create(['user_id' => $login->id]);

        Livewire::actingAs($admin)
            ->test(CreateStaff::class)
            ->fillForm(['name' => 'Dr Second', 'role' => 'consultant', 'user_id' => $login->id])
            ->call('create')
            ->assertHasFormErrors(['user_id']);
    }

    public function test_the_same_week_cannot_be_scheduled_twice(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString();
        OncallSchedule::create(['week_start' => $sunday]);

        Livewire::actingAs($admin)
            ->test(CreateOncallSchedule::class)
            ->fillForm(['week_start' => $sunday])
            ->call('create')
            ->assertHasFormErrors(['week_start']);
    }

    public function test_a_week_must_start_on_sunday(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CreateOncallSchedule::class)
            ->fillForm(['week_start' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDay()->toDateString()])
            ->call('create')
            ->assertHasFormErrors(['week_start']);
    }
}
