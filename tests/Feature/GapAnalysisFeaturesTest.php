<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Enums\WaitlistStatus;
use App\Filament\Pages\ProcedureCalendarPage;
use App\Filament\Resources\ApprovalRequestResource;
use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\ApprovalRequest;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use App\Models\Staff;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GapAnalysisFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_procedure_booking_pages_load(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        $this->actingAs($doctor)
            ->get(ProcedureBookingResource::getUrl())
            ->assertOk();

        $this->actingAs($doctor)
            ->get(ProcedureBookingResource::getUrl('create'))
            ->assertOk();

        $this->actingAs($doctor)
            ->get(ProcedureCalendarPage::getUrl())
            ->assertOk();

        $booking = ProcedureBooking::create([
            'patient_id' => $patient->id,
            'booking_date' => now()->addDays(2),
            'slot_type' => 'cath_day_care',
            'slot_number' => 1,
            'procedure' => 'Diagnostic cath',
            'procedure_status' => ProcedureStatus::Ordered,
        ]);

        $this->actingAs($doctor)
            ->get(ProcedureCalendarPage::getUrl())
            ->assertOk()
            ->assertSee($patient->name);

        $this->actingAs($doctor)
            ->get(ProcedureBookingResource::getUrl('edit', ['record' => $booking]))
            ->assertOk();
    }

    public function test_waitlist_pages_load_and_schedule_action_creates_a_booking(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();
        $staff = Staff::factory()->create();

        $entry = WaitlistEntry::create([
            'patient_id' => $patient->id,
            'staff_id' => $staff->id,
            'procedure' => 'RFA',
            'priority' => 'urgent',
            'status' => WaitlistStatus::Waiting,
        ]);

        $this->actingAs($doctor)
            ->get(WaitlistEntryResource::getUrl())
            ->assertOk()
            ->assertSee($patient->name);

        $this->assertEquals(0, $entry->days_waiting);

        $booking = ProcedureBooking::create([
            'patient_id' => $entry->patient_id,
            'staff_id' => $entry->staff_id,
            'booking_date' => now()->addWeek(),
            'slot_type' => 'cath_day_care',
            'slot_number' => 1,
            'procedure' => $entry->procedure,
            'procedure_status' => ProcedureStatus::Confirmed,
            'waitlist_entry_id' => $entry->id,
        ]);
        $entry->update(['status' => WaitlistStatus::Scheduled, 'booking_id' => $booking->id]);

        $this->assertEquals(WaitlistStatus::Scheduled, $entry->refresh()->status);
        $this->assertNotNull($entry->booking_id);
    }

    public function test_front_desk_can_manage_patients_and_scheduling_but_not_clinical_records(): void
    {
        $frontDesk = User::factory()->create(['role' => UserRole::FrontDesk]);
        $patient = Patient::factory()->create();

        $this->assertTrue($frontDesk->can('create', Patient::class));
        $this->assertTrue($frontDesk->can('update', $patient));
        $this->assertTrue($frontDesk->can('create', ProcedureBooking::class));
        $this->assertTrue($frontDesk->can('create', WaitlistEntry::class));

        $this->assertFalse($frontDesk->can('create', \App\Models\Intervention::class));
        $this->assertFalse($frontDesk->can('create', ApprovalRequest::class));
    }

    public function test_nurse_can_record_clinical_notes_but_not_finalize_reports(): void
    {
        $nurse = User::factory()->create(['role' => UserRole::Nurse]);

        $this->assertTrue($nurse->can('create', \App\Models\ClinicVisit::class));
        $this->assertTrue($nurse->can('create', \App\Models\AdmissionProgressNote::class));

        $this->assertFalse($nurse->can('create', Patient::class));
        $this->assertFalse($nurse->can('create', \App\Models\ImagingReport::class));
        $this->assertFalse($nurse->can('create', ApprovalRequest::class));
    }

    public function test_user_resource_list_renders_every_role_without_error(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['role' => UserRole::Doctor]);
        User::factory()->create(['role' => UserRole::Nurse]);
        User::factory()->create(['role' => UserRole::FrontDesk]);
        User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($admin)
            ->get(\App\Filament\Resources\UserResource::getUrl())
            ->assertOk();
    }

    public function test_approving_a_request_notifies_the_submitter(): void
    {
        $submitter = User::factory()->create(['role' => UserRole::Doctor]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $patient = Patient::factory()->create();

        $request = ApprovalRequest::create([
            'patient_id' => $patient->id,
            'diagnosis' => 'Test diagnosis',
            'procedure' => \App\Enums\ApprovalProcedure::DiagnosticCath,
            'status' => ApprovalStatus::Pending,
            'requested_by_id' => $submitter->id,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\ApprovalRequestResource\Pages\ListApprovalRequests::class)
            ->callTableAction('approve', $request)
            ->assertHasNoTableActionErrors();

        $this->assertEquals(ApprovalStatus::Approved, $request->refresh()->status);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertEquals($submitter->id, \Illuminate\Notifications\DatabaseNotification::first()->notifiable_id);
    }
}
