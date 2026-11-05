<?php

namespace App\Filament\Resources\EpStudyResource\Widgets;

use App\Enums\InterventionType;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\InterventionResource;
use App\Filament\Resources\PatientResource;
use App\Filament\Tables\Filters\ChipFilter;
use App\Models\Intervention;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

// Invasive EP procedures are stored as interventions; this puts them on the
// Electrophysiology page under the non-invasive studies.
class InvasiveEpWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return InterventionResource::canViewAny();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Invasive EP')
            ->description('EP studies, ablations and pacing implants')
            ->query(Intervention::query()->with(['patient', 'operator'])->whereIn('type', InterventionType::ofCategory('ep')))
            ->recordUrl(fn (Intervention $record) => InterventionResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                Action::make('create')
                    ->label('New invasive EP')
                    ->url(InterventionResource::getUrl('create', ['category' => 'ep']))
                    ->visible(fn () => InterventionResource::canCreate()),
            ])
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
                    ->placeholder('—'),
            ])
            ->filters([
                ChipFilter::make('type')
                    ->options(collect(InterventionType::ofCategory('ep'))
                        ->reject(fn (InterventionType $type) => $type->isLegacy())
                        ->mapWithKeys(fn (InterventionType $type) => [$type->value => $type->getLabel()])
                        ->all()),

                ChipFilter::make('procedure_status')
                    ->label('Procedure Status')
                    ->options(ProcedureStatus::class),
            ])
            ->defaultSort('date', 'desc')
            ->emptyStateHeading('No invasive EP procedures yet')
            ->emptyStateIcon('heroicon-o-bolt');
    }
}
