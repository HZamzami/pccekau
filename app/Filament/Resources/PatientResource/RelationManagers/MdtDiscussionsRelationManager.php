<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Models\MdtDiscussion;
use App\Models\Staff;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MdtDiscussionsRelationManager extends RelationManager
{
    protected static string $relationship = 'mdtDiscussions';

    protected static ?string $title = 'MDT Discussions';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    DatePicker::make('discussion_date')->required(),

                    TextInput::make('age_snapshot')
                        ->label('Age at Discussion')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-calculated from patient DOB'),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->step(0.1)
                        ->minValue(0)
                        ->suffix('kg'),

                    TextInput::make('oxygen_saturation')
                        ->label('O₂ Saturation (%)')
                        ->numeric()
                        ->step(1)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),

                    Select::make('specialist_fellow_id')
                        ->label('Specialist / Fellow')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),
                ]),

                TextInput::make('contact_number'),
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
                    Textarea::make('history')->rows(4),
                    Textarea::make('exam_findings')->label('Exam Findings')->rows(4),
                ]),

                Grid::make(2)->schema([
                    Textarea::make('echo_findings')->label('Echo Findings')->rows(4),
                    Textarea::make('cath_findings')->label('Cath Findings')->rows(4),
                ]),
            ]),

            Section::make('Discussion Outcome')->schema([
                Textarea::make('discussion_results')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('discussion_date')
            ->columns([
                TextColumn::make('discussion_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('age_snapshot')->label('Age'),

                TextColumn::make('diagnosis')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('reason_for_discussion')
                    ->label('Reason')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('specialistFellow.name')->label('Specialist / Fellow'),

                TextColumn::make('discussion_results')
                    ->label('Results')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (MdtDiscussion $record) => route('mdt-discussions.pdf', $record))
                    ->openUrlInNewTab(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('discussion_date', 'desc');
    }
}
