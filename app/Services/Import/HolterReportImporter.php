<?php

namespace App\Services\Import;

use App\Enums\EpStudyType;
use App\Enums\PatientStatus;
use App\Enums\ProcedureStatus;
use App\Models\EpStudy;
use App\Services\Import\Concerns\ParsesImportValues;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        } else {
            $this->fillIfBlank($patient, 'name', $name);
        }

        $studies = [];

        if (! $this->blank($holterReport)) {
            $studies[] = new EpStudy([
                'type' => EpStudyType::Holter,
                'date' => $date,
                'report' => $holterReport,
                'procedure_status' => ProcedureStatus::Done,
            ]);
        }

        if (! $this->blank($stressReport)) {
            $studies[] = new EpStudy([
                'type' => EpStudyType::StressEcg,
                'date' => $date,
                'report' => $stressReport,
                'procedure_status' => ProcedureStatus::Done,
            ]);
        }

        return ImportRowResult::ok($raw, $patient, $isNew, $studies);
    }

    /** @return array{created: int, updated: int, failed: int} */
    public function commit(Collection $rows): array
    {
        $created = 0;
        $updated = 0;
        $failed = 0;

        foreach ($rows as $result) {
            if ($result->status === 'error') {
                $failed++;

                continue;
            }

            try {
                DB::transaction(function () use ($result) {
                    $result->patient->save();

                    foreach ($result->childRecords as $child) {
                        $child->patient_id = $result->patient->id;
                        $child->save();
                    }
                });

                $result->isNewPatient ? $created++ : $updated++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => $failed];
    }
}
