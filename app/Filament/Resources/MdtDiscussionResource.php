<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MdtDiscussionResource\Pages;
use App\Models\MdtDiscussion;
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
use Filament\Tables\Table;

class MdtDiscussionResource extends Resource
{
    protected static ?string $model = MdtDiscussion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

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
                        ->searchable()
                        ->preload()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                    DatePicker::make('discussion_date')
                        ->required(),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('age_snapshot')
                        ->label('Age at Discussion')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-calculated from patient DOB'),

                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->minValue(0),

                    TextInput::make('oxygen_saturation')
                        ->label('O₂ Saturation (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('specialist_fellow')
                        ->label('Specialist / Fellow'),

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
                    ->sortable(),

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

                TextColumn::make('specialist_fellow')
                    ->label('Specialist / Fellow'),

                TextColumn::make('discussion_results')
                    ->label('Results')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),
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
