<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\InterventionType;
use App\Filament\Resources\InterventionResource;
use App\Filament\Tables\Filters\ChipFilter;
use Filament\Forms\Components\Section;
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
use Illuminate\Database\Eloquent\Builder;

// The chart shows interventions one category per tab (Surgery, Cath, and
// Other when there is any); each tab is a subclass of this.
abstract class InterventionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interventions';

    /** Key into InterventionType::CATEGORIES; null lists the uncategorised "Other" procedures. */
    protected static ?string $category = null;

    /** @return array<InterventionType> */
    protected static function types(): array
    {
        return static::$category ? InterventionType::ofCategory(static::$category) : [InterventionType::Other];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema(InterventionResource::detailsSchema()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('type', static::types()))
            ->recordTitleAttribute('name')
            ->columns([
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
                    ->placeholder('—'),

                TextColumn::make('notes')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->filters([
                ChipFilter::make('type')
                    ->options(collect(static::types())->mapWithKeys(fn (InterventionType $type) => [$type->value => $type->getLabel()])->all())
                    ->hidden(count(static::types()) < 2),
            ])
            ->headerActions([
                CreateAction::make()->url(fn () => InterventionResource::getUrl('create', array_filter(['patient_id' => $this->getOwnerRecord()->getKey(), 'category' => static::$category])))->openUrlInNewTab(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->url(fn ($record) => InterventionResource::getUrl('edit', ['record' => $record]))->openUrlInNewTab(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
