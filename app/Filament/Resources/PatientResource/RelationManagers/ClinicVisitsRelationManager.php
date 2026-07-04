<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Models\ClinicVisit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClinicVisitsRelationManager extends RelationManager
{
    protected static string $relationship = 'clinicVisits';

    protected static ?string $title = 'Clinic Visits';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    DatePicker::make('visit_date')
                        ->required()
                        ->maxDate(now()),

                    Select::make('seen_by_id')
                        ->label('Seen by')
                        ->relationship('seenBy', 'name', fn ($query) => $query->active())
                        ->searchable()
                        ->preload(),
                ]),

                DatePicker::make('next_follow_up_date'),
            ]),

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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('visit_date')
            ->columns([
                TextColumn::make('visit_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('seenBy.name')
                    ->label('Seen by'),

                TextColumn::make('assessment')
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 50 ? $column->getState() : null),

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
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('visit_date', 'desc');
    }
}
