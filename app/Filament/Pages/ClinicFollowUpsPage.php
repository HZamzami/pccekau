<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ClinicVisitResource;
use App\Filament\Resources\PatientResource;
use App\Models\ClinicVisit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
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

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Clinic Follow-Ups';

    protected static string $view = 'filament.pages.clinic-follow-ups-page';

    public string $activeTab = 'today';

    public function getTodayCount(): int
    {
        return $this->baseQuery()->whereDate('next_follow_up_date', today())->count();
    }

    public function getUpcomingCount(): int
    {
        return $this->baseQuery()->whereDate('next_follow_up_date', '>', today())->count();
    }

    public function getOverdueCount(): int
    {
        return $this->baseQuery()->whereDate('next_follow_up_date', '<', today())->count();
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
                    ->url(fn (ClinicVisit $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('visit_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('seenBy.name')
                    ->label('Seen by'),

                TextColumn::make('assessment')
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 50 ? $column->getState() : null),

                TextColumn::make('next_follow_up_date')
                    ->label('Follow-Up Date')
                    ->date()
                    ->sortable()
                    ->color(fn (ClinicVisit $record): string => match (true) {
                        $record->isOverdue() => 'danger',
                        $record->isDueToday() => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->state(fn (ClinicVisit $record): string => $record->isOverdue()
                        ? (int) $record->next_follow_up_date->diffInDays(today()).'d'
                        : '—'
                    )
                    ->color('danger')
                    ->visible(fn () => $this->activeTab === 'overdue'),
            ])
            ->actions([
                Action::make('reschedule')
                    ->label('Done / Reschedule')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->canRecordClinicalNotes() ?? false)
                    ->form([
                        DatePicker::make('next_follow_up_date')
                            ->label('Next follow-up (leave blank to mark done)')
                            ->minDate(today()),
                    ])
                    ->fillForm(fn (ClinicVisit $record) => ['next_follow_up_date' => null])
                    ->action(fn (ClinicVisit $record, array $data) => $record->update([
                        'next_follow_up_date' => $data['next_follow_up_date'] ?? null,
                    ])),

                EditAction::make()
                    ->label('Open Visit')
                    ->url(fn (ClinicVisit $record) => ClinicVisitResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('next_follow_up_date', 'asc')
            ->emptyStateHeading('No follow-ups found')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    private function baseQuery(): Builder
    {
        return ClinicVisit::followUpEligible()->whereNotNull('next_follow_up_date');
    }

    protected function getQuery(): Builder
    {
        $query = $this->baseQuery()->with(['patient', 'seenBy']);

        return match ($this->activeTab) {
            'upcoming' => $query->whereDate('next_follow_up_date', '>', today()),
            'overdue' => $query->whereDate('next_follow_up_date', '<', today()),
            default => $query->whereDate('next_follow_up_date', today()),
        };
    }
}
