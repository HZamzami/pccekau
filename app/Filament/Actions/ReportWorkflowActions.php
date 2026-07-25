<?php

namespace App\Filament\Actions;

use App\Enums\ReportStatus;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Status transitions for models using the HasReportWorkflow trait
 * (imaging reports, EP studies). Shared by resource tables and the
 * patient chart's relation managers.
 */
class ReportWorkflowActions
{
    /** @return array<Action> */
    public static function make(): array
    {
        $canWrite = fn () => auth()->user()?->canWrite() ?? false;

        return [
            Action::make('markPreliminary')
                ->label('Mark Preliminary')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->visible(fn (Model $record) => $canWrite() && $record->status === ReportStatus::Draft)
                ->action(fn (Model $record) => $record->markPreliminary()),

            Action::make('finalize')
                ->label('Finalize')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (Model $record) => $canWrite()
                    && in_array($record->status, [ReportStatus::Draft, ReportStatus::Preliminary, ReportStatus::Amended], true))
                ->requiresConfirmation()
                ->modalDescription(fn (Model $record) => $record->hasSigner()
                    ? 'Finalizing locks this report against further edits. Amendments will be tracked.'
                    : 'A signing physician must be set before the report can be finalized.')
                ->modalSubmitAction(fn ($action, Model $record) => $action->disabled(! $record->hasSigner()))
                ->action(function (Model $record) {
                    if (! $record->hasSigner()) {
                        Notification::make()
                            ->danger()
                            ->title('Set a signing physician first')
                            ->body('Choose a Reader / Signing physician on the report, save, then finalize.')
                            ->send();

                        return;
                    }

                    $record->finalize();
                }),

            Action::make('amend')
                ->label('Amend')
                ->icon('heroicon-o-pencil-square')
                ->color('danger')
                ->visible(fn (Model $record) => $canWrite() && $record->status === ReportStatus::Final)
                ->form([
                    Textarea::make('reason')
                        ->label('Amendment reason')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->modalDescription('This reopens a finalized report for editing. The reason is recorded in the audit log.')
                ->action(fn (Model $record, array $data) => $record->amend($data['reason'])),
        ];
    }
}
