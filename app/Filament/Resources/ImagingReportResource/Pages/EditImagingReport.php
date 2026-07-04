<?php

namespace App\Filament\Resources\ImagingReportResource\Pages;

use App\Enums\ReportStatus;
use App\Filament\Resources\ImagingReportResource;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;

class EditImagingReport extends EditRecord
{
    protected static string $resource = ImagingReportResource::class;

    protected function getHeaderActions(): array
    {
        $canWrite = fn () => auth()->user()?->canWrite() ?? false;

        return [
            Actions\Action::make('markPreliminary')
                ->label('Mark Preliminary')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn () => $canWrite() && $this->record->status === ReportStatus::Draft)
                ->action(function () {
                    $this->record->markPreliminary();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('finalize')
                ->label('Finalize')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $canWrite()
                    && in_array($this->record->status, [ReportStatus::Draft, ReportStatus::Preliminary, ReportStatus::Amended], true))
                ->requiresConfirmation()
                ->modalDescription(fn () => $this->record->signed_by
                    ? 'Finalizing locks this report against further edits. Amendments will be tracked.'
                    : 'A signing physician must be set (and saved) before the report can be finalized.')
                ->modalSubmitAction(fn ($action) => $action->disabled(blank($this->record->signed_by)))
                ->action(function () {
                    if (blank($this->record->signed_by)) {
                        return;
                    }

                    $this->record->finalize();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('amend')
                ->label('Amend')
                ->icon('heroicon-o-pencil-square')
                ->color('danger')
                ->visible(fn () => $canWrite() && $this->record->status === ReportStatus::Final)
                ->form([
                    Textarea::make('reason')
                        ->label('Amendment reason')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->modalDescription('This reopens a finalized report for editing. The reason is recorded in the audit log.')
                ->action(function (array $data) {
                    $this->record->amend($data['reason']);
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('pdf')
                ->label('PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('imaging-reports.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\DeleteAction::make(),
        ];
    }
}
