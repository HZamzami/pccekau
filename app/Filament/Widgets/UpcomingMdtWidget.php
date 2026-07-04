<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MdtDiscussionResource;
use App\Filament\Resources\PatientResource;
use App\Models\MdtDiscussion;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingMdtWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming MDT Discussions';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MdtDiscussion::query()
                    ->with(['patient', 'specialistFellow'])
                    ->whereDate('discussion_date', '>=', today())
                    ->orderBy('discussion_date')
            )
            ->recordUrl(fn (MdtDiscussion $record) => MdtDiscussionResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('discussion_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('patient.mrn')
                    ->label('MRN'),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->url(fn (MdtDiscussion $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('age_snapshot')
                    ->label('Age'),

                TextColumn::make('diagnosis')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('reason_for_discussion')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('specialistFellow.name')
                    ->label('Specialist / Fellow')
                    ->placeholder('—'),
            ]);
    }
}
