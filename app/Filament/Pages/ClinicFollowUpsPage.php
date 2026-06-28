<?php

namespace App\Filament\Pages;

use App\Models\ClinicVisit;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClinicFollowUpsPage extends Page implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Clinic Follow-Ups';
    protected static ?string $navigationGroup = 'Clinical';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Clinic Follow-Ups';
    protected static string $view = 'filament.pages.clinic-follow-ups-page';

    public string $activeTab = 'today';

    public function getTodayCount(): int
    {
        return ClinicVisit::whereDate('next_follow_up_date', today())->count();
    }

    public function getUpcomingCount(): int
    {
        return ClinicVisit::whereDate('next_follow_up_date', '>', today())->count();
    }

    public function getOverdueCount(): int
    {
        return ClinicVisit::whereNotNull('next_follow_up_date')
            ->whereDate('next_follow_up_date', '<', today())
            ->count();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->columns([
                TextColumn::make('patient.mrn')
                    ->label('MRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable()
                    ->url(fn (ClinicVisit $record) => route('filament.admin.resources.patients.edit', $record->patient)),

                TextColumn::make('visit_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('seen_by')
                    ->sortable(),

                TextColumn::make('assessment')
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 50 ? $column->getState() : null),

                TextColumn::make('next_follow_up_date')
                    ->label('Follow-Up Date')
                    ->date()
                    ->sortable()
                    ->color(fn (ClinicVisit $record): string => match (true) {
                        $record->isOverdue()  => 'danger',
                        $record->isDueToday() => 'warning',
                        default               => 'success',
                    }),

                TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->state(fn (ClinicVisit $record): string => $record->isOverdue()
                        ? $record->next_follow_up_date->diffInDays(today()) . 'd'
                        : '—'
                    )
                    ->color('danger')
                    ->visible(fn () => $this->activeTab === 'overdue'),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (ClinicVisit $record) => route('filament.admin.resources.patients.edit', $record->patient)),
            ])
            ->defaultSort(fn () => $this->activeTab === 'overdue' ? 'next_follow_up_date' : 'next_follow_up_date', fn () => $this->activeTab === 'overdue' ? 'asc' : 'asc')
            ->emptyStateHeading('No follow-ups found')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    protected function getQuery(): Builder
    {
        return match ($this->activeTab) {
            'today' => ClinicVisit::with('patient')
                ->whereDate('next_follow_up_date', today()),

            'upcoming' => ClinicVisit::with('patient')
                ->whereDate('next_follow_up_date', '>', today())
                ->orderBy('next_follow_up_date'),

            'overdue' => ClinicVisit::with('patient')
                ->whereNotNull('next_follow_up_date')
                ->whereDate('next_follow_up_date', '<', today())
                ->orderBy('next_follow_up_date'),

            default => ClinicVisit::with('patient')->whereDate('next_follow_up_date', today()),
        };
    }
}
