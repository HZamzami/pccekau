<?php

namespace App\Filament\Pages;

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

    public function mount(): void
    {
        $this->gridWeekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
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
            ->query(OncallSchedule::query()->orderByDesc('week_start'))
            ->columns([
                TextColumn::make('week_start')
                    ->label('Week Start')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (OncallSchedule $r) => $r->week_start->isSameWeek(now()) ? 'primary' : null),

                TextColumn::make('clinicStaff.name')->label('Clinic'),
                TextColumn::make('inpatientStaff.name')->label('Inpatient'),
                TextColumn::make('consultationStaff.name')->label('Consultation'),
                TextColumn::make('cathStaff.name')->label('Cath'),

                TextColumn::make('today_oncall')
                    ->label("Today's On-Call")
                    ->state(fn (OncallSchedule $r) => $r->week_start->isSameWeek(now()) ? $r->today_oncall : '—')
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
            ->query(ConsultantSchedule::query()->orderByDesc('week_start'))
            ->columns([
                TextColumn::make('week_start')
                    ->label('Week Start')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn (ConsultantSchedule $r) => $r->week_start->isSameWeek(now()) ? 'primary' : null),

                TextColumn::make('serviceStaff.name')->label('Service'),
                TextColumn::make('cathStaff.name')->label('Cath'),
                TextColumn::make('epStaff.name')->label('EP'),
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
