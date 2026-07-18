<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MdtDiscussionResource\Pages;
use App\Filament\Resources\PatientResource;
use App\Models\ImagingReport;
use App\Models\MdtDiscussion;
use App\Models\Patient;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MdtDiscussionResource extends Resource
{
    protected static ?string $model = MdtDiscussion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Case Discussion';

    protected static ?string $pluralModelLabel = 'Case Discussions';

    protected static ?string $recordTitleAttribute = 'diagnosis';

    public static function getGloballySearchableAttributes(): array
    {
        return ['diagnosis', 'reason_for_discussion', 'discussion_results'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Patient' => $record->patient?->name,
            'Date'    => $record->discussion_date?->format('d M Y'),
            'Age'     => $record->age_snapshot,
        ];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    Select::make('patient_id')->default(fn () => request()->integer('patient_id') ?: null)
                        ->label('Patient')
                        ->relationship('patient', 'name')
                        ->searchable(['name', 'mrn'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('imagingReports', []))
                        ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                    DatePicker::make('discussion_date')
                        ->required(),
                ]),

                Select::make('imagingReports')
                    ->label('Reports presented')
                    ->multiple()
                    ->relationship(
                        'imagingReports',
                        'id',
                        modifyQueryUsing: fn (Builder $query, Get $get) => $query->where('patient_id', $get('patient_id')),
                    )
                    ->getOptionLabelFromRecordUsing(fn (ImagingReport $record) => "{$record->type->getLabel()} — {$record->date->format('d M Y')} ({$record->status->getLabel()})")
                    ->visible(fn (Get $get) => filled($get('patient_id')))
                    ->preload()
                    ->columnSpanFull(),

                Grid::make(3)->schema([
                    TextInput::make('age_snapshot')
                        ->label('Age at Discussion')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-calculated from patient DOB'),

                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0)
                        ->maxValue(250)
                        ->suffix('kg'),

                    TextInput::make('oxygen_saturation')
                        ->label('O₂ Saturation (%)')
                        ->numeric()
                        ->step(1)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),
                ]),

                Grid::make(2)->schema([
                    Select::make('specialist_fellow_id')
                        ->label('Specialist / Fellow')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    TextInput::make('contact_number'),
                ]),
            ]),

            Section::make('Clinical Information')->schema([
                Textarea::make('diagnosis')
                    ->rows(2)
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('reason_for_discussion')
                    ->rows(2)
                    ->required()
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    Textarea::make('history')
                        ->rows(4),

                    Textarea::make('exam_findings')
                        ->label('Exam Findings')
                        ->rows(4),
                ]),

                Grid::make(2)->schema([
                    Textarea::make('echo_findings')
                        ->label('Echo Findings')
                        ->rows(4),

                    Textarea::make('cath_findings')
                        ->label('Cath Findings')
                        ->rows(4),
                ]),
            ]),

            Section::make('Discussion Outcome')->schema([
                Toggle::make('discussed')
                    ->label('Discussed')
                    ->helperText('Mark once the case has been presented and discussed.')
                    ->default(false),

                Textarea::make('discussion_results')
                    ->label('Discussion Results')
                    ->rows(4)
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
                    ->url(fn (MdtDiscussion $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('discussion_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('age_snapshot')
                    ->label('Age'),

                TextColumn::make('diagnosis')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('reason_for_discussion')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('specialistFellow.name')
                    ->label('Specialist / Fellow'),

                TextColumn::make('discussion_results')
                    ->label('Results')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                IconColumn::make('discussed')
                    ->label('Discussed')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('discussed')
                    ->label('Discussed')
                    ->trueLabel('Discussed')
                    ->falseLabel('Not discussed yet')
                    ->placeholder('All'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (MdtDiscussion $record) => route('mdt-discussions.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('discussion_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMdtDiscussions::route('/'),
            'create' => Pages\CreateMdtDiscussion::route('/create'),
            'edit'   => Pages\EditMdtDiscussion::route('/{record}/edit'),
        ];
    }
}
