<?php

namespace App\Filament\Pages;

use App\Services\Import\CasesDiscussionImporter;
use App\Services\Import\CathListImporter;
use App\Services\Import\HolterReportImporter;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportPatientData extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    // Reachable only via the "Import" button on the Patients list — an
    // occasional bulk utility doesn't need a permanent place in the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Import Patient Data';

    protected static string $view = 'filament.pages.import-patient-data';

    public ?array $data = [];

    /** @var array<int, array{index: int, status: string, mrn: ?string, name: ?string, message: ?string, changeSummary: string, fieldChanges: array, childSummary: array, isNewPatient: bool}>|null */
    public ?array $previewRows = null;

    /** @var array<int, bool>|null row index => included in the import */
    public ?array $selected = null;

    public string $statusFilter = 'all';

    public string $search = '';

    public ?string $sourceLabel = null;

    /** @var array{created: int, updated: int, failed: int, failures: array}|null */
    public ?array $summary = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('import_type')
                    ->label('Source file type')
                    ->options([
                        'cases_discussion' => 'Cases Discussion (Responses)',
                        'cath_list' => 'Cath & Imaging List',
                        'holter' => 'Holter Reports',
                    ])
                    ->helperText(fn ($state) => match ($state) {
                        'cases_discussion' => 'Google Forms export: MRN, Name, Age, Gender, Diagnosis, Discussion Date, etc. Creates patients + MDT Discussions.',
                        'cath_list' => 'Cath/MRI/CT booking list: MRN, Name, Nationality, Date, Procedure, Cost. Creates patients + Interventions.',
                        'holter' => 'MRN, Name, Date of Holter Hookup, Holter Report, Stress ECG Report. Creates patients + EP Studies.',
                        default => 'Choose the kind of spreadsheet you\'re uploading.',
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($set) => $set('file', null))
                    ->native(false),

                FileUpload::make('file')
                    ->label('Excel file (.xlsx)')
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->required()
                    ->live(),
            ])
            ->statePath('data');
    }

    public function analyze(): void
    {
        $state = $this->form->getState();
        $path = Storage::disk('local')->path($state['file']);
        $this->sourceLabel = basename($state['file']);

        $rows = $this->importer($state['import_type'])->preview($path);

        $this->previewRows = $rows->values()->map(fn ($row, int $index) => [
            'index' => $index,
            'status' => $row->status,
            'mrn' => $row->raw['mrn'] ?? null,
            'name' => $row->raw['name'] ?? null,
            'message' => $row->error,
            'changeSummary' => $row->changeSummary(),
            'fieldChanges' => $row->fieldChanges,
            'childSummary' => $row->childSummary,
            'isNewPatient' => $row->isNewPatient,
        ])->all();

        $this->selected = collect($this->previewRows)
            ->mapWithKeys(fn ($row) => [$row['index'] => $row['status'] !== 'error'])
            ->all();

        $this->statusFilter = 'all';
        $this->search = '';
        $this->summary = null;
    }

    /** @return array<int, array<string, mixed>> */
    public function getFilteredRowsProperty(): array
    {
        if ($this->previewRows === null) {
            return [];
        }

        $search = trim(strtolower($this->search));

        return collect($this->previewRows)
            ->when($this->statusFilter !== 'all', fn ($rows) => $rows->where('status', $this->statusFilter))
            ->when($search !== '', fn ($rows) => $rows->filter(
                fn ($row) => str_contains(strtolower((string) $row['mrn']), $search)
                    || str_contains(strtolower((string) $row['name']), $search)
            ))
            ->values()
            ->all();
    }

    /** @return array{new: int, update: int, error: int, total: int} */
    public function getStatusCountsProperty(): array
    {
        $rows = collect($this->previewRows ?? []);

        return [
            'new' => $rows->where('status', 'new')->count(),
            'update' => $rows->where('status', 'update')->count(),
            'error' => $rows->where('status', 'error')->count(),
            'total' => $rows->count(),
        ];
    }

    public function getSelectedCountProperty(): int
    {
        return count(array_filter($this->selected ?? []));
    }

    public function toggleRow(int $index): void
    {
        $this->selected[$index] = ! ($this->selected[$index] ?? false);
    }

    public function toggleAllVisible(bool $value): void
    {
        foreach ($this->filteredRows as $row) {
            if ($row['status'] !== 'error') {
                $this->selected[$row['index']] = $value;
            }
        }
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
    }

    public function importAction(): Action
    {
        return Action::make('import')
            ->label(fn () => 'Import '.$this->selectedCount.' record(s)')
            ->color('success')
            ->icon('heroicon-o-arrow-up-tray')
            ->disabled(fn () => $this->selectedCount === 0)
            ->requiresConfirmation()
            ->modalHeading('Import these records?')
            ->modalDescription(fn () => "This creates or updates {$this->selectedCount} patient record(s). Rows left unchecked or already marked as errors are skipped.")
            ->modalSubmitActionLabel('Yes, import')
            ->action(fn () => $this->confirm());
    }

    protected function confirm(): void
    {
        $state = $this->form->getState();
        $path = Storage::disk('local')->path($state['file']);
        $importer = $this->importer($state['import_type']);

        $rows = $importer->preview($path)
            ->filter(fn ($row, int $index) => $row->status !== 'error' && ($this->selected[$index] ?? false))
            ->values();

        $this->summary = $importer->commit($rows);
        $this->previewRows = null;
        $this->selected = null;

        Notification::make()
            ->title('Import complete')
            ->body("Created {$this->summary['created']}, updated {$this->summary['updated']}, failed {$this->summary['failed']}.")
            ->success()
            ->send();
    }

    public function downloadFailedReport(): StreamedResponse
    {
        $failures = $this->summary['failures']
            ?? collect($this->previewRows ?? [])
                ->where('status', 'error')
                ->map(fn ($row) => ['label' => "{$row['mrn']} — {$row['name']}", 'reason' => $row['message']])
                ->values()
                ->all();

        return response()->streamDownload(function () use ($failures) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Patient', 'Reason']);

            foreach ($failures as $failure) {
                fputcsv($handle, [$failure['label'], $failure['reason']]);
            }

            fclose($handle);
        }, 'import-errors-'.now()->format('Y-m-d-His').'.csv');
    }

    public function startOver(): void
    {
        $this->previewRows = null;
        $this->selected = null;
        $this->summary = null;
        $this->sourceLabel = null;
        $this->statusFilter = 'all';
        $this->search = '';
        $this->form->fill();
    }

    protected function importer(string $type): CasesDiscussionImporter|CathListImporter|HolterReportImporter
    {
        return match ($type) {
            'cases_discussion' => new CasesDiscussionImporter(),
            'cath_list' => new CathListImporter(),
            'holter' => new HolterReportImporter(),
        };
    }
}
