<?php

namespace App\Filament\Widgets;

use App\Enums\WaitlistStatus;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\WaitlistEntry;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

// The wait-list's front of the queue: most urgent first, then longest
// waiting, with Schedule right there so the backlog gets worked from home.
class UrgentWaitlistWidget extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Wait-list: most urgent')
            ->query(
                WaitlistEntry::query()
                    ->with(['patient', 'staff'])
                    ->where('status', WaitlistStatus::Waiting)
                    ->orderBy('priority')
                    ->orderBy('created_at')
            )
            ->paginated([8])
            ->defaultPaginationPageOption(8)
            ->headerActions([
                Action::make('viewAll')
                    ->label('View wait-list')
                    ->link()
                    ->url(WaitlistEntryResource::getUrl('index')),
            ])
            ->columns([
                TextColumn::make('priority')
                    ->badge(),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->url(fn (WaitlistEntry $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('category')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('procedure')
                    ->placeholder('—'),

                TextColumn::make('staff.name')
                    ->label('Consultant')
                    ->placeholder('Unassigned'),

                TextColumn::make('days_waiting')
                    ->label('Waiting')
                    ->state(fn (WaitlistEntry $record) => $record->days_waiting === 1 ? '1 day' : "{$record->days_waiting} days"),
            ])
            ->actions([
                WaitlistEntryResource::scheduleAction(),
            ])
            ->emptyStateHeading('No one waiting')
            ->emptyStateIcon('heroicon-o-queue-list');
    }
}
