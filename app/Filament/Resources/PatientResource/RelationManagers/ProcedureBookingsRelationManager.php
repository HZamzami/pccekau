<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\ProcedureBookingResource;
use App\Models\ProcedureBooking;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProcedureBookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'procedureBookings';

    protected static ?string $title = 'Procedure Bookings';

    public function form(Form $form): Form
    {
        return ProcedureBookingResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('procedure')
            ->columns([
                TextColumn::make('booking_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('category')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('slot_type')
                    ->label('Slot')
                    ->formatStateUsing(fn ($state) => ProcedureBooking::$slotTypeLabels[$state] ?? $state),

                TextColumn::make('procedure')
                    ->placeholder('—'),

                TextColumn::make('staff.name')
                    ->label('Interventionist')
                    ->placeholder('—'),

                TextColumn::make('procedure_status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(ProcedureCategory::class),

                SelectFilter::make('procedure_status')
                    ->label('Status')
                    ->options(ProcedureStatus::class),
            ])
            ->headerActions([
                CreateAction::make()->url(fn () => ProcedureBookingResource::getUrl('create', ['patient_id' => $this->getOwnerRecord()->getKey()]))->openUrlInNewTab(),
            ])
            ->actions([
                EditAction::make()->url(fn ($record) => ProcedureBookingResource::getUrl('edit', ['record' => $record]))->openUrlInNewTab(),
            ])
            ->defaultSort('booking_date', 'desc');
    }
}
