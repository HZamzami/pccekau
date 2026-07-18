<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ApprovalRequestResource\Pages\ListApprovalRequests;
use App\Models\ApprovalRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_can_be_created_without_a_procedure_date(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $patient = Patient::factory()->create();

        Livewire::actingAs($doctor)
            ->test(\App\Filament\Resources\ApprovalRequestResource\Pages\CreateApprovalRequest::class)
            ->fillForm([
                'patient_id'     => $patient->id,
                'procedure_date' => null,
                'diagnosis'      => 'Large secundum ASD',
                'procedure'      => 'interventional_cath_device',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = ApprovalRequest::first();
        $this->assertNull($request->procedure_date);
        $this->assertSame(ApprovalStatus::Pending, $request->status);
    }

    public function test_list_shows_patient_details_and_pending_status(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $request = ApprovalRequest::factory()->create([
            'diagnosis' => 'Severe pulmonary stenosis',
        ]);

        Livewire::actingAs($doctor)
            ->test(ListApprovalRequests::class)
            ->assertCanSeeTableRecords([$request])
            ->assertSee('Severe pulmonary stenosis')
            ->assertSee('Pending');
    }

    public function test_approve_and_reject_actions_flip_status(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $toApprove = ApprovalRequest::factory()->create();
        $toReject = ApprovalRequest::factory()->create();

        Livewire::actingAs($doctor)
            ->test(ListApprovalRequests::class)
            ->callTableAction('approve', $toApprove)
            ->callTableAction('reject', $toReject)
            ->assertHasNoTableActionErrors();

        $this->assertSame(ApprovalStatus::Approved, $toApprove->refresh()->status);
        $this->assertSame(ApprovalStatus::Rejected, $toReject->refresh()->status);

        // Once decided, the quick actions disappear.
        Livewire::actingAs($doctor)
            ->test(ListApprovalRequests::class)
            ->assertTableActionHidden('approve', $toApprove)
            ->assertTableActionHidden('reject', $toApprove);
    }

    public function test_status_filter_narrows_the_list(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $pending = ApprovalRequest::factory()->create();
        $approved = ApprovalRequest::factory()->create(['status' => ApprovalStatus::Approved]);

        Livewire::actingAs($doctor)
            ->test(ListApprovalRequests::class)
            ->filterTable('status', ApprovalStatus::Pending->value)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    }

    public function test_requests_cascade_with_patient_soft_delete(): void
    {
        $request = ApprovalRequest::factory()->create();

        $request->patient->delete();

        $this->assertSoftDeleted('approval_requests', ['id' => $request->id]);
    }
}
