<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\InterventionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InterventionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interventions';

    protected static ?string $title = 'Interventions';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(3)->schema([
                    DatePicker::make('date')
                        ->required()
                        ->maxDate(now()),

                    Select::make('type')
                        ->options(InterventionType::class)
                        ->required(),

                    Select::make('operator_id')
                        ->label('Operator')
                        ->relationship('operator', 'name', fn ($query) => $query->active())
                        ->searchable()
                        ->preload(),
                ]),

                TextInput::make('name')
                    ->label('Procedure')
                    ->placeholder('e.g. BT shunt, Glenn, Fontan, ASD device closure')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('name')
                    ->label('Procedure')
                    ->searchable(),

                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->placeholder('—'),

                TextColumn::make('notes')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(InterventionType::class),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
