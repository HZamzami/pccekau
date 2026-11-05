<?php

namespace App\Services\Import;

use App\Enums\EpStudyType;
use App\Enums\InterventionType;
use App\Enums\PatientStatus;
use App\Enums\ProcedureStatus;
use App\Models\EpStudy;
use App\Models\Intervention;
use App\Services\Import\Concerns\ParsesImportValues;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CathListImporter
{
    use ParsesImportValues;

    /** @return Collection<int, ImportRowResult> */
    public function preview(string $filePath): Collection
    {
        $this->batchPatients = [];

        $spreadsheet = IOFactory::load($filePath);
        $results = collect();

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $rows = $spreadsheet->getSheetByName($sheetName)->toArray(null, true, true, false);
            $header = $this->mapHeaders(array_shift($rows) ?? []);

            if (! isset($header['mrn'])) {
                continue;
            }

            $results = $results->merge(
                collect($rows)
                    ->filter(fn (array $row) => ! $this->blank($this->cell($row, $header, 'MRN')))
                    ->map(fn (array $row) => $this->parseRow($row, $header, $sheetName))
            );
        }

        return $results->values();
    }

    protected function parseRow(array $row, array $header, string $sheetName): ImportRowResult
    {
        $this->resetRowChanges();

        $mrn = $this->normalizeMrn($this->cell($row, $header, 'MRN'));
        $name = $this->cell($row, $header, 'Name');
        $procedure = $this->cell($row, $header, 'Procedure');

        $raw = compact('mrn', 'name') + ['sheet' => $sheetName];

        if ($this->blank($mrn)) {
            return ImportRowResult::error($raw, 'Missing MRN');
        }

        if ($this->blank($name)) {
            return ImportRowResult::error($raw, 'Missing patient name');
        }

        [$patient, $isNew] = $this->findOrNewPatient($mrn);

        if ($isNew) {
            $patient->name = $name;
            $patient->status = PatientStatus::Active;
            $this->recordChange($patient, 'name', $patient->name, 'Name');
        } else {
            $this->fillIfBlank($patient, 'name', $name, 'Name');
        }

        $this->fillIfBlank($patient, 'nationality', $this->cell($row, $header, 'nationality'), 'Nationality');

        $notesParts = array_filter([
            ($diagnosis = $this->cell($row, $header, 'Diagnosis in English') ?? $this->cell($row, $header, 'Diagnosis')) ? "Diagnosis: {$diagnosis}" : null,
            ($cost = $this->cell($row, $header, 'Cost')) ? "Cost: {$cost}" : null,
            "Imported from: {$sheetName} sheet",
        ]);

        $date = $this->parseFlexibleDate($this->cell($row, $header, 'Date if avaliable') ?? $this->cell($row, $header, 'Date if available'));
        $name = $procedure ?: 'Unspecified procedure';
        $notes = implode("\n", $notesParts) ?: null;

        // Ablations are invasive EP, which lives with the other EP studies.
        $record = str_contains(strtolower((string) $procedure), 'ablation')
            ? new EpStudy([
                'date' => $date,
                'type' => EpStudyType::Ablation,
                'procedure' => $name,
                'procedure_status' => ProcedureStatus::Requested,
                'report' => '',
                'notes' => $notes,
            ])
            : new Intervention([
                'date' => $date,
                'type' => $this->inferInterventionType($procedure),
                'name' => $name,
                'procedure_status' => ProcedureStatus::Requested,
                'notes' => $notes,
            ]);

        $childSummary = [
            ($record instanceof EpStudy ? 'EP study: ' : 'Intervention: ').$name
                .($date ? ' — '.$date->format('d M Y') : ' — no date'),
        ];

        return ImportRowResult::ok($raw, $patient, $isNew, [$record], $this->currentRowChanges, $childSummary);
    }

    protected function inferInterventionType(?string $procedure): InterventionType
    {
        $text = strtolower((string) $procedure);

        return match (true) {
            str_contains($text, 'diagnostic') => InterventionType::DiagnosticCath,
            str_contains($text, 'device') || str_contains($text, 'stent') || str_contains($text, 'occlud') => InterventionType::InterventionalCathDevice,
            str_contains($text, 'balloon') => InterventionType::InterventionalCathBalloon,
            default => InterventionType::Other,
        };
    }
}
