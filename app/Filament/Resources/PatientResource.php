<?php

namespace App\Filament\Resources;

use App\Enums\CardiacLesion;
use App\Enums\PatientStatus;
use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\RelationManagers\AdmissionsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ApprovalRequestsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ClinicVisitsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\EpStudiesRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ImagingReportsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\InterventionsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\MdtDiscussionsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\WaitlistEntriesRelationManager;
use App\Models\Patient;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Patients';

    protected static ?int $navigationSort = 1;

    public const PRIMARY_PHYSICIANS = [
        'Dr. Osman Al-Radi',
        'Dr. Ahmad Azhar',
        'Dr. Zaher Zaher',
        'Dr. Saud Bahidarah',
        'Dr. Naif Alkhushi',
        'Dr. Khadija Magrabi',
        'Dr. Sultan Zamzami',
    ];

    // Global search — makes patients findable from the ⌘K bar anywhere in the panel
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'mrn', 'primary_diagnosis'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'MRN' => $record->mrn,
            'Age' => $record->age,
            'Status' => $record->status?->getLabel() ?? '',
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make()
                ->schema([
                    InfolistGrid::make(5)->schema([
                        TextEntry::make('mrn')
                            ->label('MRN')
                            ->copyable()
                            ->weight('bold'),

                        TextEntry::make('phoenix_mrn')
                            ->label('Phoenix MRN')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('age')
                            ->label('Age')
                            ->state(fn (Patient $record) => $record->age)
                            ->placeholder('—'),

                        TextEntry::make('gender')
                            ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : null)
                            ->placeholder('—'),

                        TextEntry::make('status')
                            ->badge(),
                    ]),

                    InfolistGrid::make(4)->schema([
                        TextEntry::make('date_of_birth')
                            ->label('Date of birth')
                            ->date()
                            ->placeholder('—'),

                        TextEntry::make('weight_kg')
                            ->label('Weight')
                            ->formatStateUsing(fn ($state) => $state.' kg')
                            ->placeholder('—'),

                        TextEntry::make('height_cm')
                            ->label('Height')
                            ->formatStateUsing(fn ($state) => $state.' cm')
                            ->placeholder('—'),

                        TextEntry::make('nationality')
                            ->placeholder('—'),

                        TextEntry::make('referring_physician')
                            ->label('Primary physician')
                            ->placeholder('Unassigned'),

                        TextEntry::make('contact_number')
                            ->label('Contact')
                            ->placeholder('—'),
                    ]),

                    TextEntry::make('lesions')
                        ->label('Cardiac diagnosis')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => CardiacLesion::tryFrom($state)?->getLabel() ?? $state)
                        ->placeholder('None recorded')
                        ->columnSpanFull(),

                    TextEntry::make('primary_diagnosis')
                        ->label('Non cardiac diagnosis')
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('current_plan')
                        ->label('Summary & Current Plan')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Demographics')->schema([
                Grid::make(4)->schema([
                    TextInput::make('mrn')
                        ->label('MRN')
                        ->required()
                        // MRNs are per-clinic identifiers; DB enforces unique(clinic_id, mrn)
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn ($rule) => $rule->where('clinic_id', Filament::getTenant()->getKey()),
                        )
                        ->maxLength(50),

                    TextInput::make('phoenix_mrn')
                        ->label('Phoenix MRN')
                        ->maxLength(50),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                ]),

                Grid::make(3)->schema([
                    DatePicker::make('date_of_birth')
                        ->maxDate(now()),

                    Select::make('gender')
                        ->options(['male' => 'Male', 'female' => 'Female']),

                    Select::make('nationality')
                        ->searchable()
                        ->options(self::nationalities()),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('contact_number')
                        ->tel()
                        ->placeholder('+966 5X XXX XXXX'),

                    Select::make('referring_physician')
                        ->label('Primary physician')
                        ->options(array_combine(self::PRIMARY_PHYSICIANS, self::PRIMARY_PHYSICIANS))
                        ->placeholder('Unassigned')
                        ->nullable(),
                ]),
            ]),

            Section::make('Clinical Baseline')->schema([
                Grid::make(2)->schema([
                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0)
                        ->maxValue(250)
                        ->suffix('kg'),

                    TextInput::make('height_cm')
                        ->label('Height (cm)')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0)
                        ->maxValue(220)
                        ->suffix('cm'),

                ]),

                Select::make('lesions')
                    ->label('Cardiac diagnosis')
                    ->multiple()
                    ->options(CardiacLesion::class)
                    ->searchable()
                    ->columnSpanFull(),

                Textarea::make('primary_diagnosis')
                    ->label('Non cardiac diagnosis')
                    ->rows(2)
                    ->columnSpanFull(),
            ]),

            Section::make('Summary')->schema([
                Textarea::make('current_plan')
                    ->label('Summary & Current Plan')
                    ->rows(4)
                    ->columnSpanFull(),

                Select::make('status')
                    ->options(PatientStatus::class)
                    ->default(PatientStatus::Active)
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mrn')
                    ->label('MRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phoenix_mrn')
                    ->label('Phoenix MRN')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age')
                    ->label('Age')
                    ->state(fn (Patient $record) => $record->age)
                    ->placeholder('—'),

                TextColumn::make('gender')
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : null)
                    ->placeholder('—'),

                TextColumn::make('lesions')
                    ->label('Cardiac diagnosis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => CardiacLesion::tryFrom($state)?->getLabel() ?? $state)
                    ->limitList(3)
                    ->placeholder('—'),

                TextColumn::make('primary_diagnosis')
                    ->label('Non cardiac diagnosis')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PatientStatus::class),

                SelectFilter::make('lesions')
                    ->label('Lesion')
                    ->multiple()
                    ->options(CardiacLesion::labels())
                    // whereJsonContains works on pgsql jsonb and sqlite >= 3.39
                    ->query(fn (Builder $query, array $data) => filled($data['values'] ?? null)
                        ? $query->where(function (Builder $query) use ($data) {
                            foreach ($data['values'] as $value) {
                                $query->orWhereJsonContains('lesions', $value);
                            }
                        })
                        : $query),

                SelectFilter::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female']),

                TrashedFilter::make(),
            ])
            ->recordUrl(fn (Patient $record) => static::getUrl('view', ['record' => $record]))
            ->actions([
                ViewAction::make()
                    ->label('Open Chart')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn (Patient $record) => static::getUrl('view', ['record' => $record])),
                EditAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('No patients yet')
            ->emptyStateDescription('Register your first patient — their chart will hold imaging reports, clinic visits, case discussions, and documents.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ImagingReportsRelationManager::class,
            EpStudiesRelationManager::class,
            InterventionsRelationManager::class,
            WaitlistEntriesRelationManager::class,
            ClinicVisitsRelationManager::class,
            AdmissionsRelationManager::class,
            ApprovalRequestsRelationManager::class,
            MdtDiscussionsRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'view' => Pages\ViewPatient::route('/{record}'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }

    private static function nationalities(): array
    {
        return [
            // Most common at PCCEKAU first
            'Saudi Arabian' => 'Saudi Arabian',
            'Yemeni' => 'Yemeni',
            'Egyptian' => 'Egyptian',
            'Pakistani' => 'Pakistani',
            'Sudanese' => 'Sudanese',
            'Somali' => 'Somali',
            'Jordanian' => 'Jordanian',
            'Syrian' => 'Syrian',
            'Lebanese' => 'Lebanese',
            'Indian' => 'Indian',
            'Bangladeshi' => 'Bangladeshi',
            'Filipino' => 'Filipino',
            'Indonesian' => 'Indonesian',
            'Ethiopian' => 'Ethiopian',
            'Eritrean' => 'Eritrean',
            // Alphabetical remainder
            'Afghan' => 'Afghan',
            'Albanian' => 'Albanian',
            'Algerian' => 'Algerian',
            'American' => 'American',
            'Bahraini' => 'Bahraini',
            'British' => 'British',
            'Chadian' => 'Chadian',
            'Chinese' => 'Chinese',
            'Djibouti' => 'Djibouti',
            'Emirati' => 'Emirati',
            'French' => 'French',
            'German' => 'German',
            'Iraqi' => 'Iraqi',
            'Kuwaiti' => 'Kuwaiti',
            'Libyan' => 'Libyan',
            'Malaysian' => 'Malaysian',
            'Mauritanian' => 'Mauritanian',
            'Moroccan' => 'Moroccan',
            'Nepali' => 'Nepali',
            'Nigerian' => 'Nigerian',
            'Omani' => 'Omani',
            'Palestinian' => 'Palestinian',
            'Qatari' => 'Qatari',
            'Sri Lankan' => 'Sri Lankan',
            'Tunisian' => 'Tunisian',
            'Turkish' => 'Turkish',
            'Ugandan' => 'Ugandan',
        ];
    }
}
