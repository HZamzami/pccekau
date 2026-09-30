<?php

namespace Tests\Feature;

use App\Enums\CoverageRole;
use App\Enums\UserRole;
use App\Filament\Pages\SchedulesPage;
use App\Filament\Resources\ConsultantScheduleResource\Pages\CreateConsultantSchedule;
use App\Filament\Resources\OncallScheduleResource\Pages\CreateOncallSchedule;
use App\Filament\Resources\OncallScheduleResource\Pages\EditOncallSchedule;
use App\Models\ConsultantSchedule;
use App\Models\OncallSchedule;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private function sunday(): string
    {
        return Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function test_every_role_can_have_a_different_doctor_each_day(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$a, $b, $c] = Staff::factory()->count(3)->create();

        Livewire::actingAs($admin)
            ->test(CreateOncallSchedule::class)
            ->fillForm([
                'week_start' => $this->sunday(),
                'assignments' => [
                    'clinic' => [$a->id, $b->id, $c->id, $a->id, $b->id, $c->id, $a->id],
                    'cath' => [0 => $b->id, 3 => $c->id],
                    'oncall' => [2 => $a->id],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $week = OncallSchedule::forWeek($this->sunday());

        $this->assertSame($b->id, $week->staffFor(CoverageRole::Clinic, 1)->id);
        $this->assertSame($c->id, $week->staffFor(CoverageRole::Clinic, 5)->id);
        $this->assertSame($c->id, $week->staffFor(CoverageRole::Cath, 3)->id);
        $this->assertNull($week->staffFor(CoverageRole::Cath, 1));
        $this->assertSame($a->id, $week->staffFor(CoverageRole::Oncall, 2)->id);
        $this->assertSame(10, $week->assignments()->count());
    }

    public function test_whole_week_picker_fills_all_seven_days(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $doctor = Staff::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateOncallSchedule::class)
            ->fillForm(['week_start' => $this->sunday()])
            ->set('data.fill_week.consultation', $doctor->id)
            ->assertSet('data.assignments.consultation', array_fill(0, 7, $doctor->id))
            ->call('create')
            ->assertHasNoFormErrors();

        $week = OncallSchedule::forWeek($this->sunday());

        foreach (range(0, 6) as $day) {
            $this->assertSame($doctor->id, $week->staffFor(CoverageRole::Consultation, $day)->id);
        }
    }

    public function test_editing_loads_the_grid_and_clearing_a_day_removes_it(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $doctor = Staff::factory()->create();
        $week = OncallSchedule::create(['week_start' => $this->sunday()]);
        $week->syncAssignments(['inpatient' => array_fill(0, 7, $doctor->id)]);

        Livewire::actingAs($admin)
            ->test(EditOncallSchedule::class, ['record' => $week->getRouteKey()])
            ->assertFormSet(['assignments.inpatient.4' => $doctor->id])
            ->set('data.assignments.inpatient.4', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $week = OncallSchedule::forWeek($this->sunday());
        $this->assertNull($week->staffFor(CoverageRole::Inpatient, 4));
        $this->assertSame(6, $week->assignments()->count());
    }

    public function test_consultant_roles_are_per_day_too(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        [$a, $b] = Staff::factory()->count(2)->create(['role' => 'consultant']);

        Livewire::actingAs($admin)
            ->test(CreateConsultantSchedule::class)
            ->fillForm([
                'week_start' => $this->sunday(),
                'assignments' => ['consultant_ep' => [$a->id, $b->id]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $week = ConsultantSchedule::forWeek($this->sunday());
        $this->assertSame($a->id, $week->staffFor(CoverageRole::ConsultantEp, 0)->id);
        $this->assertSame($b->id, $week->staffFor(CoverageRole::ConsultantEp, 1)->id);
    }

    public function test_deleting_a_week_deletes_its_assignments(): void
    {
        $week = OncallSchedule::create(['week_start' => $this->sunday()]);
        $week->syncAssignments(['clinic' => array_fill(0, 7, Staff::factory()->create()->id)]);

        $week->delete();

        $this->assertDatabaseCount('schedule_assignments', 0);
    }

    public function test_ics_feed_has_one_event_per_assigned_day(): void
    {
        $doctor = Staff::factory()->create();
        $week = OncallSchedule::create(['week_start' => $this->sunday()]);
        $week->syncAssignments([
            'cath' => [1 => $doctor->id, 2 => $doctor->id],
            'oncall' => [4 => $doctor->id],
        ]);

        $body = $this->get($doctor->fresh()->ics_url)->assertOk()->getContent();

        $this->assertSame(2, substr_count($body, 'SUMMARY:Cath Coverage'));
        $this->assertSame(1, substr_count($body, 'SUMMARY:On-Call'));
        $this->assertStringContainsString(
            'DTSTART;VALUE=DATE:'.Carbon::parse($this->sunday())->addDays(4)->format('Ymd'),
            $body,
        );
    }

    public function test_grid_filters_by_role_and_doctor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $a = Staff::factory()->create(['name' => 'Dr Alpha']);
        $b = Staff::factory()->create(['name' => 'Dr Bravo']);

        $week = OncallSchedule::create(['week_start' => $this->sunday()]);
        $week->syncAssignments([
            'clinic' => array_fill(0, 7, $a->id),
            'cath' => array_fill(0, 7, $b->id),
        ]);

        $page = Livewire::actingAs($admin)
            ->test(SchedulesPage::class)
            ->assertSee('Dr Alpha')
            ->assertSee('Dr Bravo')
            ->call('toggleRole', 'cath')
            ->assertSet('roleFilter', ['cath']);

        $this->assertSame([CoverageRole::Cath], $page->instance()->getVisibleRoles());

        $page->call('clearGridFilters')
            ->assertSet('roleFilter', [])
            ->set('staffFilter', (string) $a->id)
            ->assertSeeHtml('py-2.5 pr-4 font-semibold text-primary-700 dark:text-primary-300')
            ->assertSee('Dr Alpha');
    }
}
