<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FellowsRotationResource\Pages;
use App\Models\FellowsRotation;
use App\Models\Staff;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FellowsRotationResource extends Resource
{
    protected static ?string $model = FellowsRotation::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Schedules';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Fellows Rotation';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(3)->schema([
                    TextInput::make('block_number')
                        ->label('Block #')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(13)
                        ->required(),

                    DatePicker::make('start_date')->required(),
                    DatePicker::make('end_date')->required(),
                ]),

                Grid::make(2)->schema([
                    Select::make('fellow_id')
                        ->label('Fellow')
                        ->options(fn () => Staff::active()->fellows()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Select::make('rotation')
                        ->options(FellowsRotation::$rotationLabels)
                        ->required(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('block_number')
                    ->label('Block')
                    ->sortable(),

                TextColumn::make('fellow.name')
                    ->label('Fellow')
                    ->sortable(),

                TextColumn::make('rotation')
                    ->formatStateUsing(fn ($state) => FellowsRotation::$rotationLabels[$state] ?? $state)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'vacation' => 'gray',
                        'elective' => 'gray',
                        'icu' => 'danger',
                        'research' => 'warning',
                        default => 'info',
                    }),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date(),
            ])
            ->filters([
                SelectFilter::make('fellow_id')
                    ->label('Fellow')
                    ->options(fn () => Staff::active()->fellows()->orderBy('name')->pluck('name', 'id')),

                SelectFilter::make('rotation')
                    ->options(FellowsRotation::$rotationLabels),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('block_number');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFellowsRotations::route('/'),
            'create' => Pages\CreateFellowsRotation::route('/create'),
            'edit' => Pages\EditFellowsRotation::route('/{record}/edit'),
        ];
    }
}
