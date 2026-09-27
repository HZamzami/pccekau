<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AdmissionResource;
use App\Filament\Resources\PatientResource;
use App\Models\Admission;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// Shift handover: every current inpatient with the clinical snapshot the
// incoming team needs. Visible to all roles — a handover belongs to the
// whole team, not a single recipient.
class HandoverBoardPage extends Page implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Handover Board';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Handover Board';

    protected static string $view = 'filament.pages.handover-board-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Admission::active()->with(['patient', 'admittedBy']))
            ->columns([
                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->description(fn (Admission $record) => $record->patient->mrn.' · '.$record->patient->age)
                    ->searchable()
                    ->sortable()
                    ->url(fn (Admission $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('ward')
                    ->label('Ward / Bed')
                    ->state(fn (Admission $record) => trim(($record->ward ?? '—').($record->bed ? " / {$record->bed}" : '')))
                    ->sortable(),

                TextColumn::make('presentation_diagnosis')
                    ->label('Presentation / Diagnosis')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 80 ? $column->getState() : null)
                    ->placeholder('—'),

                TextColumn::make('active_issues')
                    ->label('Active Issues')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 80 ? $column->getState() : null)
                    ->placeholder('—'),

                TextColumn::make('clinical_examination')
                    ->label('Clinical Examination')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 80 ? $column->getState() : null)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('investigations')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 80 ? $column->getState() : null)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('medications')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 80 ? $column->getState() : null)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('oncall_tasks')
                    ->label('On-call Tasks')
                    ->wrap()
                    ->limit(80)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 80 ? $column->getState() : null)
                    ->color('warning')
                    ->placeholder('—'),

                TextColumn::make('admitted_at')
                    ->label('Admitted')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Action::make('updateHandover')
                    ->label('Update')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn () => auth()->user()?->canRecordClinicalNotes() ?? false)
                    ->form([
                        Textarea::make('presentation_diagnosis')->label('Presentation / Diagnosis')->rows(2),
                        Textarea::make('active_issues')->label('Active Issues')->rows(2),
                        Textarea::make('clinical_examination')->label('Clinical Examination')->rows(2),
                        Textarea::make('investigations')->rows(2),
                        Textarea::make('medications')->rows(2),
                        Textarea::make('oncall_tasks')->label('On-call Tasks')->rows(2),
                    ])
                    ->fillForm(fn (Admission $record) => $record->only([
                        'presentation_diagnosis', 'active_issues', 'clinical_examination',
                        'investigations', 'medications', 'oncall_tasks',
                    ]))
                    ->modalHeading(fn (Admission $record) => "Handover — {$record->patient->name}")
                    ->action(fn (Admission $record, array $data) => $record->update($data)),

                Action::make('progressNote')
                    ->label('Note')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->canRecordClinicalNotes() ?? false)
                    ->form([
                        Textarea::make('note')
                            ->label('Progress note')
                            ->rows(4)
                            ->required(),
                    ])
                    ->modalHeading(fn (Admission $record) => "Progress note — {$record->patient->name}")
                    ->action(fn (Admission $record, array $data) => $record->progressNotes()->create([
                        'note' => $data['note'],
                        'author_id' => auth()->id(),
                        'noted_at' => now(),
                    ])),

                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-folder-open')
                    ->url(fn (Admission $record) => AdmissionResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('ward')
            ->emptyStateHeading('No inpatients right now')
            ->emptyStateDescription('Admitted patients appear here until they are discharged.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}
