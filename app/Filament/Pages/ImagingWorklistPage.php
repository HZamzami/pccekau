<?php

namespace App\Filament\Pages;

use App\Enums\ReportStatus;
use App\Filament\Resources\ImagingReportResource;
use App\Models\ImagingReport;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ImagingWorklistPage extends Page implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Imaging Worklist';
    protected static ?string $navigationGroup = 'Clinical';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Imaging Worklist — Pending Reads';
    protected static string $view = 'filament.pages.imaging-worklist-page';

    public static function getNavigationBadge(): ?string
    {
        $count = ImagingReport::whereIn('status', [ReportStatus::Draft, ReportStatus::Preliminary])->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ImagingReport::with(['patient', 'signedBy'])
                    ->whereIn('status', [ReportStatus::Draft, ReportStatus::Preliminary])
            )
            ->columns([
                TextColumn::make('patient.mrn')
                    ->label('MRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->url(fn (ImagingReport $record) => \App\Filament\Resources\PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('type')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('date')
                    ->label('Study date')
                    ->date()
                    ->sortable(),

                TextColumn::make('signedBy.name')
                    ->label('Assigned reader')
                    ->placeholder('Unassigned'),

                TextColumn::make('date_age')
                    ->label('Waiting')
                    ->state(fn (ImagingReport $record) => $record->date->diffForHumans(short: true)),
            ])
            ->headerActions([
                Action::make('newReport')
                    ->label('New Imaging Report')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => ImagingReportResource::getUrl('create'))
                    ->visible(fn () => auth()->user()?->canWrite() ?? false),
            ])
            ->actions([
                Action::make('assign')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn () => auth()->user()?->canWrite() ?? false)
                    ->form([
                        Select::make('signed_by')
                            ->label('Reader')
                            ->options(fn () => \App\Models\Staff::active()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->fillForm(fn (ImagingReport $record) => ['signed_by' => $record->signed_by])
                    ->action(fn (ImagingReport $record, array $data) => $record->update(['signed_by' => $data['signed_by']])),

                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ImagingReport $record) => ImagingReportResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('date', 'asc')
            ->emptyStateHeading('No pending reads')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
