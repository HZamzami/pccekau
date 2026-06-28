<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Filament\Resources\PatientResource\RelationManagers\ClinicVisitsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\ImagingReportsRelationManager;
use App\Filament\Resources\PatientResource\RelationManagers\MdtDiscussionsRelationManager;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Patients';

    protected static ?int $navigationSort = 1;

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

                    TextInput::make('nationality'),
                ]),

                Grid::make(3)->schema([
                    Select::make('blood_type')
                        ->options(['A+' => 'A+', 'A-' => 'A−', 'B+' => 'B+', 'B-' => 'B−', 'AB+' => 'AB+', 'AB-' => 'AB−', 'O+' => 'O+', 'O-' => 'O−'])
                        ->nullable(),

                    TextInput::make('contact_number'),

                    TextInput::make('referring_physician'),
                ]),
            ]),

            Section::make('Clinical Baseline')->schema([
                Grid::make(3)->schema([
                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->minValue(0),

                    TextInput::make('height_cm')
                        ->label('Height (cm)')
                        ->numeric()
                        ->minValue(0),

                    TextInput::make('baseline_oxygen_saturation')
                        ->label('Baseline O₂ Sat (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
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
            ->defaultSort('name');
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
}
