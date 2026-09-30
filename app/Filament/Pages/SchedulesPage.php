<?php

namespace App\Filament\Pages;

use App\Enums\CoverageRole;
use App\Filament\Resources\ConsultantScheduleResource;
use App\Filament\Resources\OncallScheduleResource;
use App\Models\ConsultantSchedule;
use App\Models\FellowsRotation;
use App\Models\OncallSchedule;
use App\Models\Staff;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class SchedulesPage extends Page implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Schedules';

    protected static ?string $navigationGroup = 'Schedules';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Schedules';

    protected static string $view = 'filament.pages.schedules-page';

    public string $activeTab = 'oncall';

    public ?string $gridWeekStart = null;

    /** @var array<string> role values to show; empty means all */
    public array $roleFilter = [];

    public ?string $staffFilter = null;

    public function mount(): void
    {
        $this->gridWeekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->reset(['roleFilter', 'staffFilter']);
        $this->resetTable();
    }

    public function previousGridWeek(): void
    {
        $this->gridWeekStart = Carbon::parse($this->gridWeekStart)->subWeek()->toDateString();
    }

    public function nextGridWeek(): void
    {
        $this->gridWeekStart = Carbon::parse($this->gridWeekStart)->addWeek()->toDateString();
    }

    public function currentGridWeek(): void
    {
        $this->gridWeekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function toggleRole(string $role): void
    {
        $this->roleFilter = in_array($role, $this->roleFilter, true)
            ? array_values(array_diff($this->roleFilter, [$role]))
            : [...$this->roleFilter, $role];
    }

    public function clearGridFilters(): void
    {
        $this->reset(['roleFilter', 'staffFilter']);
    }

    /** @return array<CoverageRole> */
    public function getTabRoles(): array
    {
        return $this->activeTab === 'consultants' ? CoverageRole::forConsultants() : CoverageRole::forCoverage();
    }

    /** @return array<CoverageRole> */
    public function getVisibleRoles(): array
    {
        $roles = $this->getTabRoles();

        return $this->roleFilter
            ? array_values(array_filter($roles, fn (CoverageRole $role) => in_array($role->value, $this->roleFilter, true)))
            : $roles;
    }

    /** @return Collection<int, string> */
    public function getStaffOptions(): Collection
    {
        $query = Staff::active()->orderBy('name');

        return ($this->activeTab === 'consultants' ? $query->consultants() : $query)->pluck('name', 'id');
    }

    public function getCurrentConsultants(): ?ConsultantSchedule
    {
        return ConsultantSchedule::forWeek($this->gridWeekStart ?? Carbon::now()->startOfWeek(Carbon::SUNDAY));
    }

    public function table(Table $table): Table
    {
        return match ($this->activeTab) {
            'oncall' => $this->oncallTable($table),
            'consultants' => $this->consultantTable($table),
            default => $this->oncallTable($table),
        };
    }

    protected function oncallTable(Table $table): Table
    {
        return $table
            ->query(OncallSchedule::query()->with('assignments.staff')->orderByDesc('week_start'))
            ->columns([
                TextColumn::make('week_start')
                    ->label('Week Start')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (OncallSchedule $r) => $r->isCurrentWeek() ? 'primary' : null),

                TextColumn::make('doctors')
                    ->label('Doctors this week')
                    ->state(fn (OncallSchedule $r) => $r->assignments->pluck('staff.name')->filter()->unique()->sort()->values()->all())
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('today_oncall')
                    ->label("Today's On-Call")
                    ->state(fn (OncallSchedule $r) => $r->today_oncall ?? '—')
                    ->badge()
                    ->color('warning'),
            ])
            ->actions([EditAction::make()
                ->url(fn (OncallSchedule $r) => OncallScheduleResource::getUrl('edit', ['record' => $r]))])
            ->emptyStateHeading('No on-call schedules entered yet')
            ->emptyStateIcon('heroicon-o-clock');
    }

    protected function consultantTable(Table $table): Table
    {
        return $table
            ->query(ConsultantSchedule::query()->with('assignments.staff')->orderByDesc('week_start'))
            ->columns([
                TextColumn::make('week_start')
                    ->label('Week Start')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (ConsultantSchedule $r) => $r->isCurrentWeek() ? 'primary' : null),

                TextColumn::make('doctors')
                    ->label('Doctors this week')
                    ->state(fn (ConsultantSchedule $r) => $r->assignments->pluck('staff.name')->filter()->unique()->sort()->values()->all())
                    ->badge()
                    ->placeholder('—'),
            ])
            ->actions([EditAction::make()
                ->url(fn (ConsultantSchedule $r) => ConsultantScheduleResource::getUrl('edit', ['record' => $r]))])
            ->emptyStateHeading('No consultant schedules entered yet')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public function getCurrentOncall(): ?OncallSchedule
    {
        return OncallSchedule::forWeek($this->gridWeekStart ?? Carbon::now()->startOfWeek(Carbon::SUNDAY));
    }

    public function getCurrentBlock(): ?FellowsRotation
    {
        return FellowsRotation::currentBlock();
    }

    public function getFellowsRotations(): Collection
    {
        return FellowsRotation::with('fellow')->orderBy('block_number')->orderBy('fellow_id')->get();
    }

    public function getFellowNames(): Collection
    {
        return Staff::active()->fellows()->orderBy('name')->pluck('name', 'id');
    }
}
