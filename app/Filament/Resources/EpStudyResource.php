<?php

namespace App\Filament\Resources;

use App\Enums\EpStudyType;
use App\Enums\ProcedureStatus;
use App\Enums\ReportStatus;
use App\Filament\Actions\ReportWorkflowActions;
use App\Filament\Resources\EpStudyResource\Pages;
use App\Models\EpStudy;
use App\Models\Patient;
use App\Models\Staff;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EpStudyResource extends Resource
{
    protected static ?string $model = EpStudy::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Electrophysiology';

    protected static ?string $modelLabel = 'EP study';

    protected static ?string $pluralModelLabel = 'EP studies';

    public static function getNavigationBadge(): ?string
    {
        $count = EpStudy::whereIn('status', [ReportStatus::Draft, ReportStatus::Preliminary])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending reads';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->description('Studies start as drafts. Set a Reader, then use Finalize to sign and lock the report — locked reports can only be reopened with the Amend action, which records the reason.')
                ->disabled(fn (?EpStudy $record) => $record?->isLocked() ?? false)
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('patient_id')->default(fn () => request()->integer('patient_id') ?: null)
                            ->label('Patient')
                            ->relationship('patient', 'name')
                            ->searchable(['name', 'mrn'])
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                        Select::make('type')
                            ->options(EpStudyType::class)
                            ->required(),
                    ]),

                    Grid::make(3)->schema([
                        DatePicker::make('date'),

                        Select::make('procedure_status')
                            ->label('Procedure Status')
                            ->options(ProcedureStatus::class)
                            ->default(ProcedureStatus::Ordered)
                            ->required(),

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
                            ->content(fn (?EpStudy $record) => $record?->status?->getLabel() ?? 'Draft'),
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
                    ->sortable()
                    ->url(fn (EpStudy $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('procedure_status')
                    ->label('Procedure Status')
                    ->badge(),

                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('signedBy.name')
                    ->label('Reader')
                    ->placeholder('Unassigned'),

                TextColumn::make('waiting')
                    ->label('Waiting')
                    ->state(fn (EpStudy $record) => $record->date && in_array($record->status, [ReportStatus::Draft, ReportStatus::Preliminary], true)
                        ? $record->date->diffForHumans(short: true)
                        : null)
                    ->placeholder('—'),

                TextColumn::make('report')
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 50 ? $column->getState() : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(EpStudyType::labels()),

                SelectFilter::make('status')
                    ->options(ReportStatus::class),

                SelectFilter::make('procedure_status')
                    ->label('Procedure Status')
                    ->options(ProcedureStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('assign')
                    ->label('Assign reader')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (EpStudy $record) => (auth()->user()?->canWrite() ?? false)
                        && in_array($record->status, [ReportStatus::Draft, ReportStatus::Preliminary], true))
                    ->form([
                        Select::make('signed_by')
                            ->label('Reader')
                            ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->fillForm(fn (EpStudy $record) => ['signed_by' => $record->signed_by])
                    ->action(fn (EpStudy $record, array $data) => $record->update(['signed_by' => $data['signed_by']])),
                ...ReportWorkflowActions::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpStudies::route('/'),
            'create' => Pages\CreateEpStudy::route('/create'),
            'edit' => Pages\EditEpStudy::route('/{record}/edit'),
        ];
    }
}
