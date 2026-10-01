<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdmissionResource\Pages;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AdmissionResource extends Resource
{
    protected static ?string $model = Admission::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Inpatients';

    public static function form(Form $form): Form
    {
        return $form->schema(static::formSchema());
    }

    /**
     * Shared with the patient-chart relation manager, which drops the
     * patient select (the owner record supplies it).
     */
    public static function formSchema(bool $withPatient = true): array
    {
        return [
            Section::make('Admission')->schema([
                Grid::make(2)->schema(array_filter([
                    $withPatient ? Select::make('patient_id')->default(fn () => request()->integer('patient_id') ?: null)
                        ->label('Patient')
                        ->relationship('patient', 'name')
                        ->searchable(['name', 'mrn'])
                        ->preload()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}") : null,

                    DateTimePicker::make('admitted_at')
                        ->label('Admitted at')
                        ->default(now())
                        ->required(),
                ])),

                Grid::make(3)->schema([
                    TextInput::make('ward')
                        ->maxLength(255),

                    TextInput::make('bed')
                        ->maxLength(50),

                    Select::make('admitted_by_id')
                        ->label('Admitted by')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                ]),

                Textarea::make('admission_note')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
            ]),

            Section::make('Handover')
                ->description('Kept current during the stay — this is what the incoming shift sees on the Handover Board.')
                ->schema([
                    Grid::make(2)->schema([
                        Textarea::make('presentation_diagnosis')
                            ->label('Presentation / Diagnosis')
                            ->rows(3),

                        Textarea::make('active_issues')
                            ->label('Active Issues')
                            ->rows(3),

                        Textarea::make('clinical_examination')
                            ->label('Clinical Examination')
                            ->rows(3),

                        Textarea::make('investigations')
                            ->rows(3),

                        Textarea::make('medications')
                            ->rows(3),

                        Textarea::make('oncall_tasks')
                            ->label('On-call Tasks')
                            ->rows(3),
                    ]),
                ]),

            Section::make('Progress Notes')->schema([
                Repeater::make('progressNotes')
                    ->relationship()
                    ->label('')
                    ->schema([
                        Textarea::make('note')
                            ->rows(3)
                            ->required(),
                    ])
                    ->itemLabel(fn (array $state) => filled($state['noted_at'] ?? null)
                        ? Carbon::parse($state['noted_at'])->format('d M Y H:i').' — '.(User::find($state['author_id'] ?? null)?->name ?? 'Unknown')
                        : 'New note')
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data) {
                        $data['author_id'] = auth()->id();
                        $data['noted_at'] = now();

                        return $data;
                    })
                    ->addActionLabel('Add progress note')
                    ->deletable(fn () => auth()->user()?->isAdmin() ?? false)
                    ->defaultItems(0),
            ]),

            Section::make('Discharge')
                ->schema([
                    Grid::make(2)->schema([
                        DateTimePicker::make('discharged_at')
                            ->label('Discharged at')
                            ->disabled(),

                        Select::make('discharged_by_id')
                            ->label('Discharged by')
                            ->options(fn () => Staff::orderBy('name')->pluck('name', 'id'))
                            ->disabled(),
                    ]),

                    Textarea::make('discharge_note')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->visible(fn (?Admission $record) => $record?->discharged_at !== null),
        ];
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
                    ->url(fn (Admission $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('ward')
                    ->state(fn (Admission $record) => trim(($record->ward ?? '—').($record->bed ? " / bed {$record->bed}" : '')))
                    ->label('Ward / Bed'),

                TextColumn::make('admitted_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('admittedBy.name')
                    ->label('Admitted by')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Admission $record) => $record->isActive() ? 'Admitted' : 'Discharged')
                    ->color(fn (string $state) => $state === 'Admitted' ? 'success' : 'gray'),

                TextColumn::make('stay')
                    ->label('Stay')
                    ->state(fn (Admission $record) => $record->stayLabel()),
            ])
            ->filters([
                TernaryFilter::make('admitted')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Currently admitted')
                    ->falseLabel('Discharged')
                    ->queries(
                        true: fn ($query) => $query->whereNull('discharged_at'),
                        false: fn ($query) => $query->whereNotNull('discharged_at'),
                    )
                    ->default(true),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                static::dischargeAction(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('admitted_at', 'desc')
            ->emptyStateHeading('No admissions')
            ->emptyStateDescription('Admit a patient to start tracking their inpatient stay and handover.');
    }

    // Shared by the resource table and the patient-chart relation manager.
    public static function dischargeAction(): Action
    {
        return Action::make('discharge')
            ->label('Discharge')
            ->icon('heroicon-o-arrow-right-start-on-rectangle')
            ->color('warning')
            ->visible(fn (Admission $record) => $record->isActive() && (auth()->user()?->canWrite() ?? false))
            ->form([
                Textarea::make('discharge_note')
                    ->rows(4)
                    ->required(),

                Select::make('discharged_by_id')
                    ->label('Discharged by')
                    ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
            ])
            ->modalHeading(fn (Admission $record) => "Discharge {$record->patient->name}")
            ->action(fn (Admission $record, array $data) => $record->update([
                'discharged_at' => now(),
                'discharge_note' => $data['discharge_note'],
                'discharged_by_id' => $data['discharged_by_id'] ?? null,
            ]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmissions::route('/'),
            'create' => Pages\CreateAdmission::route('/create'),
            'edit' => Pages\EditAdmission::route('/{record}/edit'),
        ];
    }
}
