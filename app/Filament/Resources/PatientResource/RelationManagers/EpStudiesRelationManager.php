<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\EpStudyType;
use App\Enums\ReportStatus;
use App\Filament\Actions\ReportWorkflowActions;
use App\Models\EpStudy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class EpStudiesRelationManager extends RelationManager
{
    protected static string $relationship = 'epStudies';

    protected static ?string $title = 'Electrophysiology';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->disabled(fn (?EpStudy $record) => $record?->isLocked() ?? false)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('type')
                            ->options(EpStudyType::class)
                            ->required(),

                        DatePicker::make('date')
                            ->required()
                            ->maxDate(now()),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('performed_by_id')
                            ->label('Performed by')
                            ->relationship('performedBy', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload(),

                        Select::make('signed_by')
                            ->label('Reader / Signing physician')
                            ->relationship('signedBy', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload(),
                    ]),

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
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('signedBy.name')
                    ->label('Reader')
                    ->placeholder('Unassigned'),

                TextColumn::make('report')
                    ->limit(60)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 60 ? $column->getState() : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(EpStudyType::labels()),

                SelectFilter::make('status')
                    ->options(ReportStatus::class),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                ...ReportWorkflowActions::make(),
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
