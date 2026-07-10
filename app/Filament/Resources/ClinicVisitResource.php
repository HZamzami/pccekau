<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClinicVisitResource\Pages;
use App\Filament\Resources\PatientResource;
use App\Models\ClinicVisit;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClinicVisitResource extends Resource
{
    protected static ?string $model = ClinicVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    Select::make('patient_id')
                        ->label('Patient')
                        ->relationship('patient', 'name')
                        ->searchable(['name', 'mrn'])
                        ->preload()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                    DatePicker::make('visit_date')
                        ->required()
                        ->maxDate(now()),
                ]),

                Grid::make(2)->schema([
                    Select::make('seen_by_id')
                        ->label('Seen by')
                        ->relationship('seenBy', 'name', fn ($query) => $query->active())
                        ->searchable()
                        ->preload(),

                    DatePicker::make('next_follow_up_date'),
                ]),
            ]),


            Section::make('Vitals')->schema([
                Grid::make(3)->schema([
                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->minValue(0.3)
                        ->maxValue(250),

                    TextInput::make('height_cm')
                        ->label('Height (cm)')
                        ->numeric()
                        ->minValue(20)
                        ->maxValue(220),

                    TextInput::make('oxygen_saturation')
                        ->label('O2 Sat (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),

                    TextInput::make('heart_rate')
                        ->label('Heart rate (bpm)')
                        ->numeric()
                        ->minValue(20)
                        ->maxValue(300),

                    TextInput::make('bp_systolic')
                        ->label('BP systolic')
                        ->numeric()
                        ->minValue(30)
                        ->maxValue(250),

                    TextInput::make('bp_diastolic')
                        ->label('BP diastolic')
                        ->numeric()
                        ->minValue(10)
                        ->maxValue(150),
                ]),
            ])->collapsible(),

            Section::make('SOAP Note')->schema([
                Textarea::make('subjective')
                    ->label('Subjective — Patient complaints & history')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('objective')
                    ->label('Objective — Vitals & exam findings')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('assessment')
                    ->label('Assessment — Diagnosis / impression')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('plan')
                    ->label('Plan — Medications, referrals, follow-up')
                    ->rows(4)
                    ->required()
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
                    ->url(fn (ClinicVisit $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('visit_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('seenBy.name')
                    ->label('Seen by')
                    ->sortable(),

                TextColumn::make('oxygen_saturation')
                    ->label('O₂ Sat')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->placeholder('—'),

                TextColumn::make('assessment')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('next_follow_up_date')
                    ->label('Next Follow-Up')
                    ->date()
                    ->sortable()
                    ->color(fn (ClinicVisit $record): string => match (true) {
                        $record->isOverdue()  => 'danger',
                        $record->isDueToday() => 'warning',
                        default               => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('overdue')
                    ->label('Overdue follow-ups')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('next_follow_up_date')
                        ->whereDate('next_follow_up_date', '<', today())),

                Filter::make('today')
                    ->label('Due today')
                    ->query(fn (Builder $query) => $query
                        ->whereDate('next_follow_up_date', today())),

                Filter::make('upcoming')
                    ->label('Upcoming follow-ups')
                    ->query(fn (Builder $query) => $query
                        ->whereDate('next_follow_up_date', '>', today())),
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
            ->defaultSort('visit_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClinicVisits::route('/'),
            'create' => Pages\CreateClinicVisit::route('/create'),
            'edit'   => Pages\EditClinicVisit::route('/{record}/edit'),
        ];
    }
}
