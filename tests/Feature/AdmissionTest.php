<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\HandoverBoardPage;
use App\Filament\Resources\AdmissionResource\Pages\ListAdmissions;
use App\Models\Admission;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admissions_appear_on_the_handover_board(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $inpatient = Admission::factory()->create(['presentation_diagnosis' => 'TOF repair, post-op day 2']);
        $discharged = Admission::factory()->discharged()->create();

        Livewire::actingAs($doctor)
            ->test(HandoverBoardPage::class)
            ->assertCanSeeTableRecords([$inpatient])
            ->assertCanNotSeeTableRecords([$discharged])
            ->assertSee('TOF repair, post-op day 2');
    }

    public function test_admissions_list_defaults_to_currently_admitted(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $active = Admission::factory()->create();
        $discharged = Admission::factory()->discharged()->create();

        Livewire::actingAs($doctor)
            ->test(ListAdmissions::class)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$discharged]);

        Livewire::actingAs($doctor)
            ->test(ListAdmissions::class)
            ->filterTable('admitted', false)
            ->assertCanSeeTableRecords([$discharged])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_discharge_action_requires_a_note_and_clears_the_board(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $admission = Admission::factory()->create();

        Livewire::actingAs($doctor)
            ->test(ListAdmissions::class)
            ->callTableAction('discharge', $admission, ['discharge_note' => ''])
            ->assertHasTableActionErrors(['discharge_note']);

        Livewire::actingAs($doctor)
            ->test(ListAdmissions::class)
            ->callTableAction('discharge', $admission, ['discharge_note' => 'Home on aspirin, review in clinic in 2 weeks.'])
            ->assertHasNoTableActionErrors();

        $admission->refresh();
        $this->assertNotNull($admission->discharged_at);
        $this->assertFalse($admission->isActive());

        Livewire::actingAs($doctor)
            ->test(HandoverBoardPage::class)
            ->assertCanNotSeeTableRecords([$admission]);
    }

    public function test_progress_notes_accumulate_with_authors(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $admission = Admission::factory()->create();

        Livewire::actingAs($doctor)
            ->test(HandoverBoardPage::class)
            ->callTableAction('progressNote', $admission, ['note' => 'Stable overnight.'])
            ->callTableAction('progressNote', $admission, ['note' => 'Chest drain removed.'])
            ->assertHasNoTableActionErrors();

        $this->assertSame(2, $admission->progressNotes()->count());
        $this->assertSame($doctor->id, $admission->progressNotes()->first()->author_id);
    }

    public function test_handover_update_is_shared_not_per_user(): void
    {
        $doctorA = User::factory()->create(['role' => UserRole::Doctor]);
        $doctorB = User::factory()->create(['role' => UserRole::Viewer]);
        $admission = Admission::factory()->create();

        Livewire::actingAs($doctorA)
            ->test(HandoverBoardPage::class)
            ->callTableAction('updateHandover', $admission, [
                'active_issues' => 'Desaturations overnight — needs review',
                'oncall_tasks'  => 'Repeat gas at 22:00',
            ])
            ->assertHasNoTableActionErrors();

        // The incoming shift (any role, read-only included) sees the same board.
        Livewire::actingAs($doctorB)
            ->test(HandoverBoardPage::class)
            ->assertSee('Desaturations overnight — needs review')
            ->assertSee('Repeat gas at 22:00')
            ->assertTableActionHidden('updateHandover', $admission);
    }

    public function test_admissions_are_isolated_per_clinic(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);

        $otherClinic = Clinic::factory()->create();
        $foreignPatient = Patient::factory()->create(['clinic_id' => $otherClinic->id]);
        $foreignAdmission = Admission::factory()->create([
            'clinic_id'  => $otherClinic->id,
            'patient_id' => $foreignPatient->id,
        ]);

        Livewire::actingAs($doctor)
            ->test(HandoverBoardPage::class)
            ->assertCanNotSeeTableRecords([$foreignAdmission]);
    }

    public function test_admissions_cascade_with_patient_soft_delete(): void
    {
        $admission = Admission::factory()->create();
        $patient = $admission->patient;

        $patient->delete();
        $this->assertSoftDeleted('admissions', ['id' => $admission->id]);

        $patient->restore();
        $this->assertNotSoftDeleted('admissions', ['id' => $admission->id]);
    }
}
