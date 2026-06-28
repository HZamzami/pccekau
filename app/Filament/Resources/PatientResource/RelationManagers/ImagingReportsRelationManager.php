<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Models\ImagingReport;
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

class ImagingReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'imagingReports';

    protected static ?string $title = 'Imaging Reports';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    Select::make('type')
                        ->options(ImagingReport::$typeLabels)
                        ->required(),

                    DatePicker::make('date')
                        ->required()
                        ->maxDate(now()),
                ]),

                TextInput::make('performed_by'),

                Textarea::make('report')
                    ->required()
                    ->rows(8)
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
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')
                    ->formatStateUsing(fn ($state) => ImagingReport::$typeLabels[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('performed_by'),

                TextColumn::make('report')
                    ->limit(60)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 60 ? $column->getState() : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ImagingReport::$typeLabels),
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
