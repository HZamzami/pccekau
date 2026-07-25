<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\ImagingType;
use App\Enums\ReportStatus;
use App\Filament\Resources\ImagingReportResource;
use App\Models\ImagingReport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
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
            Section::make()
                ->disabled(fn (?ImagingReport $record) => $record?->isLocked() ?? false)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('type')
                            ->options(ImagingType::selectableLabels())
                            ->required(),

                        DatePicker::make('date')
                            ->required()
                            ->maxDate(now()),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('performers')
                            ->label('Performed by')
                            ->multiple()
                            ->relationship('performers', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload(),

                        Select::make('readers')
                            ->label('Reader / Signing physician')
                            ->multiple()
                            ->relationship('readers', 'name', fn ($query) => $query->active())
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

                TextColumn::make('readers.name')
                    ->label('Reader')
                    ->listWithLineBreaks()
                    ->placeholder('Unassigned'),

                TextColumn::make('report')
                    ->limit(60)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 60 ? $column->getState() : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ImagingType::labels()),

                SelectFilter::make('status')
                    ->options(ReportStatus::class),
            ])
            ->headerActions([
                CreateAction::make()->url(fn () => ImagingReportResource::getUrl('create', ['patient_id' => $this->getOwnerRecord()->getKey()]))->openUrlInNewTab(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->url(fn ($record) => ImagingReportResource::getUrl('edit', ['record' => $record]))->openUrlInNewTab(),
                ...ImagingReportResource::workflowActions(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (ImagingReport $record) => route('imaging-reports.pdf', $record))
                    ->openUrlInNewTab(),
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
