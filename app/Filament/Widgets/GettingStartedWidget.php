<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Filament\Resources\ImagingReportResource;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\StaffResource;
use App\Filament\Resources\UserResource;
use App\Models\ImagingReport;
use App\Models\Patient;
use App\Models\Staff;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class GettingStartedWidget extends Widget
{
    protected static string $view = 'filament.widgets.getting-started-widget';

    protected static ?int $sort = -3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if ($user === null || $user->getting_started_dismissed_at !== null) {
            return false;
        }

        return collect((new self)->getSteps())->contains(fn (array $step) => ! $step['done']);
    }

    /** @return array<array{label: string, description: string, done: bool, url: ?string}> */
    public function getSteps(): array
    {
        $user = auth()->user();
        $canWrite = $user?->canWrite() ?? false;

        $steps = [
            [
                'label' => 'Add your staff',
                'description' => 'Consultants and fellows must exist before they can be assigned to reports, visits, and on-call schedules.',
                'done' => Staff::query()->exists(),
                'url' => $canWrite ? StaffResource::getUrl('create') : null,
            ],
            [
                'label' => 'Register a patient',
                'description' => 'Each patient gets a chart holding their reports, clinic visits, case discussions, and documents.',
                'done' => Patient::query()->exists(),
                'url' => $canWrite ? PatientResource::getUrl('create') : null,
            ],
            [
                'label' => 'Write an imaging report',
                'description' => 'Open a patient\'s chart (or Clinical → Imaging Reports) and create a report. Echo reports include measurements with automatic z-scores.',
                'done' => ImagingReport::query()->exists(),
                'url' => $canWrite ? ImagingReportResource::getUrl('create') : null,
            ],
            [
                'label' => 'Finalize a report',
                'description' => 'Set a signing physician, then use the Finalize action. Finalized reports are locked — use Amend to correct them.',
                // Nested where: a bare orWhere would OR past the clinic
                // global scope and leak other clinics' reports.
                'done' => ImagingReport::query()
                    ->where(fn ($query) => $query
                        ->where('status', ReportStatus::Final)
                        ->orWhereNotNull('finalized_at'))
                    ->exists(),
                'url' => null,
            ],
        ];

        if ($user?->isAdmin()) {
            $steps[] = [
                'label' => 'Add accounts for your colleagues',
                'description' => 'Admins manage everything, doctors write clinical records, viewers are read-only.',
                'done' => User::whereBelongsTo(Filament::getTenant())->count() > 1,
                'url' => UserResource::getUrl('create'),
            ];
        }

        return $steps;
    }

    public function dismiss(): void
    {
        auth()->user()?->forceFill(['getting_started_dismissed_at' => now()])->save();
    }
}
