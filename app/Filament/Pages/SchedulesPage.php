<?php

namespace App\Filament\Pages;

use App\Models\ConsultantSchedule;
use App\Models\FellowsRotation;
use App\Models\OncallSchedule;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return match ($this->activeTab) {
            'oncall'      => $this->oncallTable($table),
            'consultants' => $this->consultantTable($table),
            default       => $this->oncallTable($table),
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

                TextColumn::make('hijri_date_range')->label('Hijri'),
                TextColumn::make('clinic_doctor')->label('Clinic'),
                TextColumn::make('inpatient_doctor')->label('Inpatient'),
                TextColumn::make('cath_doctor')->label('Cath'),
                TextColumn::make('service_doctor')->label('Service'),
                TextColumn::make('ep_doctor')->label('EP'),

                TextColumn::make('today_oncall')
                    ->label("Today's On-Call")
                    ->state(fn (OncallSchedule $r) => $r->week_start->isSameWeek(now()) ? $r->today_oncall : '—')
                    ->badge()
                    ->color('warning'),
            ])
            ->actions([EditAction::make()
                ->url(fn (OncallSchedule $r) => route('filament.admin.resources.oncall-schedules.edit', $r))])
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

                TextColumn::make('hijri_date')->label('Hijri'),
                TextColumn::make('service_consultant')->label('Service'),
                TextColumn::make('cath_consultant')->label('Cath'),
                TextColumn::make('ep_consultant')->label('EP'),
            ])
            ->actions([EditAction::make()
                ->url(fn (ConsultantSchedule $r) => route('filament.admin.resources.consultant-schedules.edit', $r))])
            ->emptyStateHeading('No consultant schedules entered yet')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public function getCurrentOncall(): ?OncallSchedule
    {
        return OncallSchedule::currentWeek();
    }

    public function getCurrentBlock(): ?FellowsRotation
    {
        return FellowsRotation::currentBlock();
    }

    public function getFellowsRotations(): Collection
    {
        return FellowsRotation::orderBy('block_number')->orderBy('fellow_name')->get();
    }

    public function getFellowNames(): Collection
    {
        return FellowsRotation::query()->distinct()->orderBy('fellow_name')->pluck('fellow_name');
    }
}
