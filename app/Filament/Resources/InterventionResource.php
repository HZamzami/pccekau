<?php

namespace App\Filament\Resources;

use App\Enums\InterventionType;
use App\Filament\Resources\InterventionResource\Pages;
use App\Models\Intervention;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InterventionResource extends Resource
{
    protected static ?string $model = Intervention::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Select::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'name')
                    ->searchable(['name', 'mrn'])
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                ...static::detailsSchema(),
            ]),
        ]);
    }

    /** @return array<\Filament\Forms\Components\Component> Fields shared with the patient chart's relation manager. */
    public static function detailsSchema(): array
    {
        return [
            Grid::make(3)->schema([
                DatePicker::make('date')
                    ->required()
                    ->maxDate(now()),

                Select::make('type')
                    ->options(InterventionType::class)
                    ->required(),

                Select::make('operator_id')
                    ->label('Operator')
                    ->relationship('operator', 'name', fn ($query) => $query->active())
                    ->searchable()
                    ->preload(),
            ]),

            TextInput::make('name')
                ->label('Procedure')
                ->placeholder('e.g. BT shunt, Glenn, Fontan, ASD device closure')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('notes')
                ->rows(3)
                ->columnSpanFull(),
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
                    ->url(fn (Intervention $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('date')
                    ->date()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge(),

                TextColumn::make('name')
                    ->label('Procedure')
                    ->searchable(),

                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('notes')
                    ->limit(40)
                    ->placeholder('—')
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(InterventionType::class),

                SelectFilter::make('operator_id')
                    ->label('Operator')
                    ->relationship('operator', 'name', fn ($query) => $query->active())
                    ->searchable()
                    ->preload(),

                Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('date', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('date', '<=', $date)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        $data['from'] ? 'From ' . \Illuminate\Support\Carbon::parse($data['from'])->format('d M Y') : null,
                        $data['until'] ? 'Until ' . \Illuminate\Support\Carbon::parse($data['until'])->format('d M Y') : null,
                    ])),
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
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInterventions::route('/'),
            'create' => Pages\CreateIntervention::route('/create'),
            'edit'   => Pages\EditIntervention::route('/{record}/edit'),
        ];
    }
}
