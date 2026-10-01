<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\WaitlistStatus;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\WaitlistEntry;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WaitlistEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'waitlistEntries';

    protected static ?string $title = 'Wait-list';

    public function form(Form $form): Form
    {
        return WaitlistEntryResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('procedure')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Added')
                    ->date()
                    ->sortable(),

                TextColumn::make('category')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('procedure')
                    ->placeholder('—'),

                TextColumn::make('staff.name')
                    ->label('Consultant')
                    ->placeholder('Unassigned'),

                TextColumn::make('priority')
                    ->badge(),

                TextColumn::make('days_waiting')
                    ->label('Days Waiting')
                    ->state(fn (WaitlistEntry $record) => $record->days_waiting),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('booking.booking_date')
                    ->label('Booked For')
                    ->date()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(WaitlistStatus::class),
            ])
            ->headerActions([
                CreateAction::make()->url(fn () => WaitlistEntryResource::getUrl('create', ['patient_id' => $this->getOwnerRecord()->getKey()]))->openUrlInNewTab(),
            ])
            ->actions([
                WaitlistEntryResource::scheduleAction(),
                WaitlistEntryResource::removeAction(),
                EditAction::make()->url(fn ($record) => WaitlistEntryResource::getUrl('edit', ['record' => $record]))->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
