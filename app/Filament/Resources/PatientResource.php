<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\RelationManagers\ClinicVisitsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ImagingReportsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\MdtDiscussionsRelationManager;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Patients';

    protected static ?int $navigationSort = 1;

    // Global search — makes patients findable from the ⌘K bar anywhere in the panel
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'mrn', 'primary_diagnosis'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'MRN'    => $record->mrn,
            'Age'    => $record->age,
            'Status' => ucfirst($record->status),
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Demographics')->schema([
                Grid::make(3)->schema([
                    TextInput::make('mrn')
                        ->label('MRN')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50),

                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                ]),

                Grid::make(3)->schema([
                    DatePicker::make('date_of_birth')
                        ->required()
                        ->maxDate(now()),

                    Select::make('gender')
                        ->options(['male' => 'Male', 'female' => 'Female'])
                        ->required(),

                    Select::make('nationality')
                        ->searchable()
                        ->options(self::nationalities()),
                ]),

                Grid::make(3)->schema([
                    Select::make('blood_type')
                        ->options(['A+' => 'A+', 'A-' => 'A−', 'B+' => 'B+', 'B-' => 'B−', 'AB+' => 'AB+', 'AB-' => 'AB−', 'O+' => 'O+', 'O-' => 'O−'])
                        ->placeholder('Not known')
                        ->nullable(),

                    TextInput::make('contact_number')
                        ->tel()
                        ->placeholder('+966 5X XXX XXXX'),

                    TextInput::make('referring_physician'),
                ]),
            ]),

            Section::make('Clinical Baseline')->schema([
                Grid::make(3)->schema([
                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0)
                        ->suffix('kg'),

                    TextInput::make('height_cm')
                        ->label('Height (cm)')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0)
                        ->suffix('cm'),

                    TextInput::make('baseline_oxygen_saturation')
                        ->label('Baseline O₂ Sat (%)')
                        ->numeric()
                        ->step(1)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),
                ]),

                Textarea::make('primary_diagnosis')
                    ->rows(2)
                    ->columnSpanFull(),
            ]),

            Section::make('History & Plan')->schema([
                Textarea::make('surgical_history')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('current_plan')
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('status')
                    ->options([
                        'active'     => 'Active',
                        'follow-up'  => 'Follow-Up',
                        'post-op'    => 'Post-Op',
                        'discharged' => 'Discharged',
                    ])
                    ->default('active')
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

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('age')
                    ->label('Age')
                    ->state(fn (Patient $record) => $record->age),

                TextColumn::make('gender')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('primary_diagnosis')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'     => 'success',
                        'follow-up'  => 'warning',
                        'post-op'    => 'info',
                        'discharged' => 'gray',
                        default      => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'     => 'Active',
                        'follow-up'  => 'Follow-Up',
                        'post-op'    => 'Post-Op',
                        'discharged' => 'Discharged',
                    ]),

                SelectFilter::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female']),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->label('Open Chart')
                    ->icon('heroicon-o-folder-open'),
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
            ->emptyStateDescription('Register your first patient — their chart will hold imaging reports, clinic visits, MDT discussions, and documents.');
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
            ClinicVisitsRelationManager::class,
            MdtDiscussionsRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit'   => Pages\EditPatient::route('/{record}/edit'),
        ];
    }

    private static function nationalities(): array
    {
        return [
            // Most common at PCCEKAU first
            'Saudi Arabian'  => 'Saudi Arabian',
            'Yemeni'         => 'Yemeni',
            'Egyptian'       => 'Egyptian',
            'Pakistani'      => 'Pakistani',
            'Sudanese'       => 'Sudanese',
            'Somali'         => 'Somali',
            'Jordanian'      => 'Jordanian',
            'Syrian'         => 'Syrian',
            'Lebanese'       => 'Lebanese',
            'Indian'         => 'Indian',
            'Bangladeshi'    => 'Bangladeshi',
            'Filipino'       => 'Filipino',
            'Indonesian'     => 'Indonesian',
            'Ethiopian'      => 'Ethiopian',
            'Eritrean'       => 'Eritrean',
            // Alphabetical remainder
            'Afghan'         => 'Afghan',
            'Albanian'       => 'Albanian',
            'Algerian'       => 'Algerian',
            'American'       => 'American',
            'Bahraini'       => 'Bahraini',
            'British'        => 'British',
            'Chadian'        => 'Chadian',
            'Chinese'        => 'Chinese',
            'Djibouti'       => 'Djibouti',
            'Emirati'        => 'Emirati',
            'French'         => 'French',
            'German'         => 'German',
            'Iraqi'          => 'Iraqi',
            'Kuwaiti'        => 'Kuwaiti',
            'Libyan'         => 'Libyan',
            'Malaysian'      => 'Malaysian',
            'Mauritanian'    => 'Mauritanian',
            'Moroccan'       => 'Moroccan',
            'Nepali'         => 'Nepali',
            'Nigerian'       => 'Nigerian',
            'Omani'          => 'Omani',
            'Palestinian'    => 'Palestinian',
            'Qatari'         => 'Qatari',
            'Sri Lankan'     => 'Sri Lankan',
            'Tunisian'       => 'Tunisian',
            'Turkish'        => 'Turkish',
            'Ugandan'        => 'Ugandan',
        ];
    }
}
