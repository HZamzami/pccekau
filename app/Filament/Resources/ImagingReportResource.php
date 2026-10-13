<?php

namespace App\Filament\Resources;

use App\Enums\ImagingType;
use App\Enums\ProcedureStatus;
use App\Enums\ReportStatus;
use App\Filament\Actions\ReportWorkflowActions;
use App\Filament\Resources\ImagingReportResource\Pages;
use App\Models\ImagingReport;
use App\Models\Patient;
use App\Models\Staff;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
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

    protected static ?string $navigationGroup = 'Reports & Documents';

    protected static ?int $navigationSort = 4;

    // Each modality gets a sub-item: the same list, filtered. They unfold
    // under Imaging Reports while any imaging page is open.
    public static function getNavigationItems(): array
    {
        $parent = parent::getNavigationItems()[0]
            ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName().'.*') && static::currentModality() === null);

        $children = collect(ImagingType::MODALITIES)
            ->map(fn (string $label, string $modality) => NavigationItem::make($label)
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationLabel())
                ->isActiveWhen(fn () => static::currentModality() === $modality)
                ->url(static::getUrl('index', ['modality' => $modality])))
            ->values()
            ->all();

        return [$parent, ...$children];
    }

    /** Modality the current page is showing, or null outside this resource or on the unfiltered list. */
    public static function currentModality(): ?string
    {
        if (! request()->routeIs(static::getRouteBaseName().'.*')) {
            return null;
        }

        if (request()->routeIs(static::getRouteBaseName().'.edit')) {
            return ImagingReport::find(request()->route('record'))?->type?->modality();
        }

        $modality = request()->query('modality');

        return is_string($modality) && array_key_exists($modality, ImagingType::MODALITIES) ? $modality : null;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ImagingReport::whereIn('status', [ReportStatus::Draft, ReportStatus::Preliminary])->count();

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
                ->description('Reports start as drafts. Set a Reader, then use Finalize to sign and lock the report — locked reports can only be reopened with the Amend action, which records the reason.')
                ->disabled(fn (?ImagingReport $record) => $record?->isLocked() ?? false)
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
                            ->options(ImagingType::selectableLabels())
                            // Opened from Fetal Echo, MRI or CT, the type is already known
                            ->default(function () {
                                $types = ImagingType::ofModality((string) request()->query('modality'));

                                return count($types) === 1 ? $types[0]->value : null;
                            })
                            ->required(),
                    ]),

                    Grid::make(3)->schema([
                        DatePicker::make('date'),

                        Select::make('procedure_status')
                            ->label('Procedure Status')
                            ->options(ProcedureStatus::class)
                            ->default(ProcedureStatus::Ordered)
                            ->required(),

                        Select::make('performers')
                            ->label('Performed by')
                            ->multiple()
                            ->relationship('performers', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('readers')
                            ->label('Reader / Signing physician')
                            ->multiple()
                            ->relationship('readers', 'name', fn ($query) => $query->active())
                            ->searchable()
                            ->preload(),

                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn (?ImagingReport $record) => $record?->status?->getLabel() ?? 'Draft'),
                    ]),

                    Placeholder::make('mdt_display')
                        ->label('Presented at Case Discussion')
                        ->content(fn (ImagingReport $record) => $record->mdtDiscussions
                            ->map(fn ($mdt) => $mdt->discussion_date->format('d M Y'))
                            ->join(', '))
                        ->visible(fn (?ImagingReport $record) => $record !== null && $record->mdtDiscussions->isNotEmpty()),

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
                    ->url(fn (ImagingReport $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

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

                TextColumn::make('readers.name')
                    ->label('Reader')
                    ->listWithLineBreaks()
                    ->placeholder('Unassigned'),

                TextColumn::make('waiting')
                    ->label('Waiting')
                    ->state(fn (ImagingReport $record) => $record->date && in_array($record->status, [ReportStatus::Draft, ReportStatus::Preliminary], true)
                        ? $record->date->diffForHumans(short: true)
                        : null)
                    ->placeholder('—'),

                TextColumn::make('mdt_discussions_count')
                    ->label('Case Discussions')
                    ->counts('mdtDiscussions')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('report')
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 50 ? $column->getState() : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ImagingType::labels()),

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
                    ->visible(fn (ImagingReport $record) => (auth()->user()?->canWrite() ?? false)
                        && in_array($record->status, [ReportStatus::Draft, ReportStatus::Preliminary], true))
                    ->form([
                        Select::make('readers')
                            ->label('Readers')
                            ->multiple()
                            ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->fillForm(fn (ImagingReport $record) => ['readers' => $record->readers->pluck('id')->all()])
                    ->action(fn (ImagingReport $record, array $data) => $record->readers()->sync($data['readers'])),
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
        return ReportWorkflowActions::make();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImagingReports::route('/'),
            'create' => Pages\CreateImagingReport::route('/create'),
            'edit' => Pages\EditImagingReport::route('/{record}/edit'),
        ];
    }
}
