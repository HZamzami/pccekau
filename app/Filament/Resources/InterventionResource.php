<?php

namespace App\Filament\Resources;

use App\Enums\InterventionType;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\InterventionResource\Pages;
use App\Filament\Tables\Filters\ChipFilter;
use App\Filament\Tables\Filters\DateRangeFilter;
use App\Models\Intervention;
use App\Models\Patient;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
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

    protected static ?string $navigationGroup = 'Reports & Documents';

    // The sidebar shows Surgery and Cath as separate pages over this one list.
    public static function getNavigationItems(): array
    {
        return [
            static::categoryNavigationItem('surgery', 'heroicon-o-scissors', 1),
            static::categoryNavigationItem('cath', 'heroicon-o-heart', 2),
        ];
    }

    protected static function categoryNavigationItem(string $category, string $icon, int $sort): NavigationItem
    {
        return NavigationItem::make(InterventionType::CATEGORIES[$category])
            ->group(static::getNavigationGroup())
            ->icon($icon)
            ->isActiveWhen(fn () => static::currentCategory() === $category)
            ->sort($sort)
            ->url(static::getUrl('index', ['category' => $category]));
    }

    /** Category the current page is showing, or null outside this resource or on the unfiltered list. */
    public static function currentCategory(): ?string
    {
        if (! request()->routeIs(static::getRouteBaseName().'.*')) {
            return null;
        }

        if (request()->routeIs(static::getRouteBaseName().'.edit')) {
            return Intervention::find(request()->route('record'))?->type?->category();
        }

        $category = request()->query('category');

        return is_string($category) && array_key_exists($category, InterventionType::CATEGORIES) ? $category : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Select::make('patient_id')->default(fn () => request()->integer('patient_id') ?: null)
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

    /** @return array<Component> Fields shared with the patient chart's relation manager. */
    public static function detailsSchema(): array
    {
        return [
            Grid::make(4)->schema([
                DatePicker::make('date'),

                Select::make('procedure_status')
                    ->label('Procedure Status')
                    ->options(ProcedureStatus::class)
                    ->default(ProcedureStatus::Requested)
                    ->required(),

                Select::make('type')
                    ->options(InterventionType::selectableLabels())
                    // Opened from Surgery, the type is already known
                    ->default(function () {
                        $types = array_filter(InterventionType::ofCategory((string) request()->query('category')), fn (InterventionType $type) => ! $type->isLegacy());

                        return count($types) === 1 ? reset($types)->value : null;
                    })
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
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('procedure_status')
                    ->label('Procedure Status')
                    ->badge(),

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
                // The unscoped "All procedures" list splits by category; the
                // Surgery and Cath pages already are one.
                ChipFilter::make('category')
                    ->quick()
                    ->hidden(fn ($livewire) => filled($livewire->category ?? null))
                    ->options(InterventionType::CATEGORIES)
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['values'] ?? null,
                        fn (Builder $query, array $categories) => $query->whereIn('type', collect($categories)
                            ->flatMap(fn (string $category) => InterventionType::ofCategory($category))
                            ->map(fn (InterventionType $type) => $type->value)
                            ->all()),
                    )),

                ChipFilter::make('procedure_status')
                    ->label('Procedure Status')
                    ->options(ProcedureStatus::class),

                ChipFilter::make('type')
                    ->quick(fn ($livewire) => filled($livewire->category ?? null))
                    ->options(fn ($livewire) => collect(filled($livewire->category ?? null) ? InterventionType::ofCategory($livewire->category) : InterventionType::cases())
                        ->mapWithKeys(fn (InterventionType $type) => [$type->value => $type->getLabel()])
                        ->all()),

                SelectFilter::make('operator_id')
                    ->multiple()
                    ->label('Operator')
                    ->relationship('operator', 'name', fn ($query) => $query->active())
                    ->searchable()
                    ->preload(),

                DateRangeFilter::make('date'),
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
            'index' => Pages\ListInterventions::route('/'),
            'create' => Pages\CreateIntervention::route('/create'),
            'edit' => Pages\EditIntervention::route('/{record}/edit'),
        ];
    }
}
