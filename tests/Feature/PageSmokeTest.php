<?php

namespace Tests\Feature;

use App\Enums\CoverageRole;
use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Filament\Pages\ProcedureCalendarPage;
use App\Filament\Pages\SchedulesPage;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\PatientResource\Pages\ViewPatient;
use App\Models\Admission;
use App\Models\ApprovalRequest;
use App\Models\ClinicVisit;
use App\Models\ConsultantSchedule;
use App\Models\EpStudy;
use App\Models\FellowsRotation;
use App\Models\ImagingReport;
use App\Models\Intervention;
use App\Models\MdtDiscussion;
use App\Models\OncallSchedule;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\ProcedureBooking;
use App\Models\Staff;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\RelationManagers\RelationGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

// Loads every page of the panel, for every role, against a clinic with one of
// everything in it. Anything that 500s shows up here before it reaches users.
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<class-string<Model>, Model> */
    private array $records = [];

    protected function setUp(): void
    {
        parent::setUp();

        $patient = Patient::factory()->create();
        $consultant = Staff::factory()->create(['role' => 'consultant']);
        $fellow = Staff::factory()->create(['role' => 'fellow']);
        $sunday = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        $oncall = OncallSchedule::create(['week_start' => $sunday]);
        $oncall->syncAssignments([
            'clinic' => array_fill(0, 7, $fellow->id),
            'oncall' => [now()->dayOfWeek => $consultant->id],
        ]);
        $consultants = ConsultantSchedule::create(['week_start' => $sunday]);
        $consultants->syncAssignments(['consultant_ep' => array_fill(0, 7, $consultant->id)]);

        $booking = ProcedureBooking::create([
            'patient_id' => $patient->id,
            'booking_date' => $sunday->copy()->addDay(),
            'slot_type' => 'mri_ct',
            'slot_number' => 1,
            'category' => ProcedureCategory::Mri,
            'staff_id' => $consultant->id,
            'procedure_status' => ProcedureStatus::Scheduled,
        ]);

        $this->records = [
            Patient::class => $patient,
            Staff::class => $consultant,
            OncallSchedule::class => $oncall,
            ConsultantSchedule::class => $consultants,
            ProcedureBooking::class => $booking,
            WaitlistEntry::class => WaitlistEntry::create([
                'patient_id' => $patient->id,
                'category' => ProcedureCategory::Surgery,
                'procedure' => 'Glenn',
            ]),
            FellowsRotation::class => FellowsRotation::create([
                'block_number' => 1,
                'start_date' => $sunday->copy()->subWeek(),
                'end_date' => $sunday->copy()->addWeeks(3),
                'fellow_id' => $fellow->id,
                'rotation' => 'icu',
            ]),
            PatientDocument::class => PatientDocument::create([
                'patient_id' => $patient->id,
                'label' => 'Referral letter',
                'files' => [],
            ]),
            Admission::class => Admission::factory()->for($patient)->create(),
            ApprovalRequest::class => ApprovalRequest::factory()->for($patient)->create(),
            ClinicVisit::class => ClinicVisit::factory()->for($patient)->create(),
            EpStudy::class => EpStudy::factory()->for($patient)->create(),
            ImagingReport::class => ImagingReport::factory()->for($patient)->create(),
            Intervention::class => Intervention::factory()->for($patient)->create(),
            MdtDiscussion::class => MdtDiscussion::factory()->for($patient)->create(),
        ];
    }

    /** @return array<string, array{UserRole}> */
    public static function roles(): array
    {
        return collect(UserRole::cases())->mapWithKeys(fn (UserRole $r) => [$r->value => [$r]])->all();
    }

    #[DataProvider('roles')]
    public function test_every_page_loads_for_every_role(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->records[User::class] = $user;
        $this->actingAs($user);

        $panel = Filament::getPanel('admin');
        $failures = [];

        foreach ($panel->getResources() as $resource) {
            $model = $resource::getModel();
            $record = $this->records[$model] ?? ($model === Activity::class ? Activity::query()->first() : null);

            foreach ($resource::getPages() as $name => $registration) {
                $needsRecord = ! is_subclass_of($registration->getPage(), ListRecords::class)
                    && ! is_subclass_of($registration->getPage(), CreateRecord::class);

                if ($needsRecord && ! $record) {
                    $failures[] = "{$resource}:{$name} has no test record";

                    continue;
                }

                $url = $resource::getUrl($name, $needsRecord ? ['record' => $record] : []);
                $status = $this->get($url)->status();

                if ($status >= 500) {
                    $failures[] = "{$status} {$url}";
                }
            }
        }

        foreach ($panel->getPages() as $page) {
            $url = $page::getUrl();
            $status = $this->get($url)->status();

            if ($status >= 500) {
                $failures[] = "{$status} {$url}";
            }
        }

        foreach (['patients.summary-pdf' => ['patient' => $this->records[Patient::class]],
            'imaging-reports.pdf' => ['report' => $this->records[ImagingReport::class]],
            'mdt-discussions.pdf' => ['discussion' => $this->records[MdtDiscussion::class]]] as $route => $params) {
            $status = $this->get(route($route, $params))->status();

            if ($status >= 500) {
                $failures[] = "{$status} {$route}";
            }
        }

        $this->assertSame([], $failures);
    }

    // Dashboard widgets and chart tabs load in their own Livewire requests
    // after the page, so the GETs above never render them.
    #[DataProvider('roles')]
    public function test_every_widget_and_patient_tab_renders_for_every_role(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);
        $patient = $this->records[Patient::class];
        $failures = [];

        $widgets = [
            ...Filament::getPanel('admin')->getWidgets(),
            ...collect(Filament::getPanel('admin')->getResources())->flatMap(fn ($resource) => $resource::getWidgets())->all(),
        ];

        foreach ($widgets as $widget) {
            if (! $widget::canView()) {
                continue;
            }

            try {
                Livewire::test($widget)->assertSuccessful();
            } catch (\Throwable $e) {
                $failures[] = class_basename($widget).': '.$e->getMessage();
            }
        }

        $managers = collect(PatientResource::getRelations())
            ->flatMap(fn ($manager) => $manager instanceof RelationGroup ? $manager->ownerRecord($patient)->pageClass(ViewPatient::class)->getManagers() : [$manager]);

        foreach ($managers as $manager) {
            if (! $manager::canViewForRecord($patient, ViewPatient::class)) {
                continue;
            }

            try {
                Livewire::test($manager, ['ownerRecord' => $patient, 'pageClass' => ViewPatient::class])->assertSuccessful();
            } catch (\Throwable $e) {
                $failures[] = class_basename($manager).': '.$e->getMessage();
            }
        }

        $this->assertSame([], $failures);
    }

    // Reported: dashboard -> any page -> dashboard gave a 500.
    public function test_dashboard_after_visiting_each_page_still_loads(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $panel = Filament::getPanel('admin');
        $dashboard = $panel->getUrl();
        $failures = [];

        $urls = collect($panel->getNavigation())
            ->flatMap(fn ($group) => $group->getItems())
            ->map(fn ($item) => $item->getUrl())
            ->filter()
            ->unique();

        foreach ($urls as $url) {
            foreach ([$dashboard, $url, $dashboard] as $step) {
                $status = $this->get($step)->status();

                if ($status >= 500) {
                    $failures[] = "{$status} {$step} (via {$url})";
                }
            }
        }

        $this->assertNotEmpty($urls);
        $this->assertSame([], $failures);
    }

    public function test_schedule_tabs_and_calendar_filters_render(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));

        $schedules = Livewire::test(SchedulesPage::class)->assertSuccessful();

        foreach (['oncall', 'fellows', 'consultants'] as $tab) {
            $schedules->call('setTab', $tab)->assertSuccessful();
        }

        $schedules->call('setTab', 'oncall')
            ->call('toggleRole', CoverageRole::Oncall->value)
            ->set('staffFilter', (string) $this->records[Staff::class]->id)
            ->call('nextGridWeek')
            ->call('previousGridWeek')
            ->assertSuccessful();

        $calendar = Livewire::test(ProcedureCalendarPage::class)->assertSuccessful();

        foreach (ProcedureCategory::cases() as $category) {
            $calendar->set('categories', [$category->value])->assertSuccessful();
        }

        $calendar->set('categories', [ProcedureCategory::Surgery->value, ProcedureCategory::Cath->value])->assertSuccessful();

        $calendar->set('statuses', [ProcedureStatus::Scheduled->value, ProcedureStatus::Postponed->value])
            ->set('staffId', (string) $this->records[Staff::class]->id)
            ->call('clearFilters')
            ->call('nextWeek')
            ->call('previousWeek')
            ->assertSuccessful();
    }
}
