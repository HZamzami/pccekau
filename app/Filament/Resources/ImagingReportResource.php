<?php

namespace App\Filament\Resources;

use App\Enums\ImagingType;
use App\Enums\ReportStatus;
use App\Filament\Forms\EchoMeasurementsSection;
use App\Filament\Resources\ImagingReportResource\Pages;
use App\Models\ImagingReport;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImagingReportResource extends Resource
{
    protected static ?string $model = ImagingReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->disabled(fn (?ImagingReport $record) => $record?->isLocked() ?? false)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('patient_id')
                            ->label('Patient')
                            ->relationship('patient', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                        Select::make('type')
                            ->options(ImagingType::class)
                            ->required()
                            ->live(),
                    ]),

                    Grid::make(2)->schema([
                        DatePicker::make('date')
                            ->required()
                            ->maxDate(now()),

                        Select::make('performed_by_id')
                            ->label('Performed by')
                            ->relationship('performedBy', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('signed_by')
                            ->label('Reader / Signing physician')
                            ->relationship('signedBy', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload(),

                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn (?ImagingReport $record) => $record?->status?->getLabel() ?? 'Draft'),
                    ]),

                    Textarea::make('report')
                        ->required()
                        ->rows(8)
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            EchoMeasurementsSection::make()
                ->disabled(fn (?ImagingReport $record) => $record?->isLocked() ?? false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.mrn')
                    ->label('MRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),

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
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 50 ? $column->getState() : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ImagingType::labels()),

                SelectFilter::make('status')
                    ->options(ReportStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                ...static::workflowActions(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (ImagingReport $record) => route('imaging-reports.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    /** @return array<Action> Status transitions shared by the table and relation manager. */
    public static function workflowActions(): array
    {
        $canWrite = fn () => auth()->user()?->canWrite() ?? false;

        return [
            Action::make('markPreliminary')
                ->label('Mark Preliminary')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn (ImagingReport $record) => $canWrite() && $record->status === ReportStatus::Draft)
                ->action(fn (ImagingReport $record) => $record->markPreliminary()),

            Action::make('finalize')
                ->label('Finalize')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (ImagingReport $record) => $canWrite()
                    && in_array($record->status, [ReportStatus::Draft, ReportStatus::Preliminary, ReportStatus::Amended], true))
                ->requiresConfirmation()
                ->modalDescription(fn (ImagingReport $record) => $record->signed_by
                    ? 'Finalizing locks this report against further edits. Amendments will be tracked.'
                    : 'A signing physician must be set before the report can be finalized.')
                ->modalSubmitAction(fn ($action, ImagingReport $record) => $record->signed_by ? null : $action->hidden())
                ->action(fn (ImagingReport $record) => $record->finalize()),

            Action::make('amend')
                ->label('Amend')
                ->icon('heroicon-o-pencil-square')
                ->color('danger')
                ->visible(fn (ImagingReport $record) => $canWrite() && $record->status === ReportStatus::Final)
                ->form([
                    Textarea::make('reason')
                        ->label('Amendment reason')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->modalDescription('This reopens a finalized report for editing. The reason is recorded in the audit log.')
                ->action(fn (ImagingReport $record, array $data) => $record->amend($data['reason'])),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListImagingReports::route('/'),
            'create' => Pages\CreateImagingReport::route('/create'),
            'edit'   => Pages\EditImagingReport::route('/{record}/edit'),
        ];
    }
}
