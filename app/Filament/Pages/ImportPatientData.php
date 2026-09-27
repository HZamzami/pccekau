<?php

namespace App\Filament\Pages;

use App\Services\Import\CasesDiscussionImporter;
use App\Services\Import\CathListImporter;
use App\Services\Import\HolterReportImporter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ImportPatientData extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Patients';

    protected static ?string $navigationLabel = 'Import Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Import Patient Data';

    protected static string $view = 'filament.pages.import-patient-data';

    public ?array $data = [];

    /** @var array<int, array{status: string, label: string, message: ?string}>|null */
    public ?array $previewRows = null;

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
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set) {
                        $set('file', null);
                        $this->resetPreview();
                    }),

                FileUpload::make('file')
                    ->label('Excel file (.xlsx)')
                    ->disk('local')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->resetPreview()),
            ])
            ->statePath('data');
    }

    public function resetPreview(): void
    {
        $this->previewRows = null;
        $this->summary = null;
    }

    public function preview(): void
    {
        $state = $this->form->getState();
        $path = Storage::disk('local')->path($state['file']);

        $rows = $this->importer($state['import_type'])->preview($path);

        $this->previewRows = $rows->map(fn ($row) => [
            'status' => $row->status,
            'label' => $row->summaryLabel(),
            'message' => $row->error,
        ])->all();

        $this->summary = null;
    }

    public function confirm(): void
    {
        $state = $this->form->getState();
        $path = Storage::disk('local')->path($state['file']);

        $importer = $this->importer($state['import_type']);
        $rows = $importer->preview($path);
        $result = $importer->commit($rows);

        $this->summary = $result;
        $this->previewRows = null;

        Notification::make()
            ->title('Import complete')
            ->body("Created {$result['created']}, updated {$result['updated']}, failed {$result['failed']}.")
            ->success()
            ->send();
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
