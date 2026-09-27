<?php

namespace App\Services\Import;

use App\Enums\EpStudyType;
use App\Enums\PatientStatus;
use App\Enums\ProcedureStatus;
use App\Models\EpStudy;
use App\Services\Import\Concerns\ParsesImportValues;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HolterReportImporter
{
    use ParsesImportValues;

    /** @return Collection<int, ImportRowResult> */
    public function preview(string $filePath): Collection
    {
        $this->batchPatients = [];

        $sheet = IOFactory::load($filePath)->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        $header = $this->mapHeaders(array_shift($rows));

        return collect($rows)
            ->filter(fn (array $row) => ! $this->blank($this->cell($row, $header, 'MRN')))
            ->map(fn (array $row) => $this->parseRow($row, $header))
            ->values();
    }

    protected function parseRow(array $row, array $header): ImportRowResult
    {
        $this->resetRowChanges();

        $mrn = $this->normalizeMrn($this->cell($row, $header, 'MRN'));
        // This sheet's second column holds the patient name but has no header
        // text of its own — read it positionally, right after MRN.
        $mrnIndex = $header['mrn'] ?? null;
        $name = $mrnIndex !== null ? ($row[$mrnIndex + 1] ?? null) : null;
        $name = is_string($name) ? trim($name) : $name;

        $holterReport = $this->cell($row, $header, 'Holter Report');
        $stressReport = $this->cell($row, $header, 'Stress ECG Report');
        $date = $this->parseFlexibleDate($this->cell($row, $header, 'Date of Holter Hookup'));

        $raw = compact('mrn', 'name');

        if ($this->blank($mrn)) {
            return ImportRowResult::error($raw, 'Missing MRN');
        }

        if ($this->blank($holterReport) && $this->blank($stressReport)) {
            return ImportRowResult::error($raw, 'No Holter or Stress ECG report text');
        }

        [$patient, $isNew] = $this->findOrNewPatient($mrn);

        if ($isNew) {
            $patient->name = $name ?: 'Unknown';
            $patient->status = PatientStatus::Active;
            $this->recordChange($patient, 'name', $patient->name, 'Name');
        } else {
            $this->fillIfBlank($patient, 'name', $name, 'Name');
        }

        $studies = [];
        $childSummary = [];
        $dateLabel = $date ? $date->format('d M Y') : 'no date';

        if (! $this->blank($holterReport)) {
            $studies[] = new EpStudy([
                'type' => EpStudyType::Holter,
                'date' => $date,
                'report' => $holterReport,
                'procedure_status' => ProcedureStatus::Done,
            ]);
            $childSummary[] = "Holter report — {$dateLabel}";
        }

        if (! $this->blank($stressReport)) {
            $studies[] = new EpStudy([
                'type' => EpStudyType::StressEcg,
                'date' => $date,
                'report' => $stressReport,
                'procedure_status' => ProcedureStatus::Done,
            ]);
            $childSummary[] = "Stress ECG report — {$dateLabel}";
        }

        return ImportRowResult::ok($raw, $patient, $isNew, $studies, $this->currentRowChanges, $childSummary);
    }
}
