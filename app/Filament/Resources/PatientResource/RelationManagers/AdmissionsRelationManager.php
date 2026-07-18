<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Filament\Resources\AdmissionResource;
use App\Models\Admission;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AdmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'admissions';

    public function form(Form $form): Form
    {
        return $form->schema(AdmissionResource::formSchema(withPatient: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('admitted_at')
            ->columns([
                TextColumn::make('admitted_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('ward')
                    ->state(fn (Admission $record) => trim(($record->ward ?? '—') . ($record->bed ? " / bed {$record->bed}" : '')))
                    ->label('Ward / Bed'),

                TextColumn::make('admittedBy.name')
                    ->label('Admitted by')
                    ->placeholder('—'),

                TextColumn::make('presentation_diagnosis')
                    ->label('Presentation / Diagnosis')
                    ->limit(40)
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Admission $record) => $record->isActive() ? 'Admitted' : 'Discharged')
                    ->color(fn (string $state) => $state === 'Admitted' ? 'success' : 'gray'),

                TextColumn::make('discharged_at')
                    ->label('Discharged')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('admitted')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Currently admitted')
                    ->falseLabel('Discharged')
                    ->queries(
                        true: fn ($query) => $query->whereNull('discharged_at'),
                        false: fn ($query) => $query->whereNotNull('discharged_at'),
                    ),
            ])
            ->headerActions([
                CreateAction::make()->url(fn () => AdmissionResource::getUrl('create', ['patient_id' => $this->getOwnerRecord()->getKey()]))->openUrlInNewTab()
                    ->label('Admit patient'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->url(fn ($record) => AdmissionResource::getUrl('edit', ['record' => $record]))->openUrlInNewTab(),
                AdmissionResource::dischargeAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('admitted_at', 'desc');
    }
}
