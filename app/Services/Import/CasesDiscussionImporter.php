<?php

namespace App\Services\Import;

use App\Enums\PatientStatus;
use App\Models\MdtDiscussion;
use App\Services\Import\Concerns\ParsesImportValues;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CasesDiscussionImporter
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
        $name = $this->cell($row, $header, 'Name');
        $diagnosis = $this->cell($row, $header, 'Diagnosis');
        $reason = $this->cell($row, $header, 'Reason for discussion');
        $discussionDate = $this->parseFlexibleDate($this->cell($row, $header, 'Discussion Date'));

        $raw = compact('mrn', 'name') + ['sheet' => 'Form Responses 1'];

        if ($this->blank($mrn)) {
            return ImportRowResult::error($raw, 'Missing MRN');
        }

        if ($this->blank($diagnosis) || $this->blank($reason) || ! $discussionDate) {
            return ImportRowResult::error($raw, 'Missing required field (diagnosis, reason for discussion, or a parseable discussion date)');
        }

        [$patient, $isNew] = $this->findOrNewPatient($mrn);

        if ($isNew) {
            $patient->name = $name ?: 'Unknown';
            $patient->status = PatientStatus::Active;
            $this->recordChange($patient, 'name', $patient->name, 'Name');
        } else {
            $this->fillIfBlank($patient, 'name', $name, 'Name');
        }

        $this->fillIfBlank($patient, 'gender', strtolower((string) $this->cell($row, $header, 'Gender')) ?: null, 'Gender');
        $this->fillIfBlank($patient, 'nationality', $this->cell($row, $header, 'Nationality'), 'Nationality');
        $this->fillIfBlank($patient, 'referring_physician', $this->cell($row, $header, 'Primary'), 'Referring physician');
        $weight = $this->parseNumeric($this->cell($row, $header, 'Weight'));
        $oxygenSaturation = $this->parseNumeric($this->cell($row, $header, 'Oxygen saturation'));

        $this->fillIfBlank($patient, 'weight_kg', $weight, 'Weight (kg)');
        $this->fillIfBlank($patient, 'contact_number', $this->cell($row, $header, 'Contact number'), 'Contact number');

        $specialist = $this->matchStaffByName($this->cell($row, $header, 'Specialist/Fellow'));

        $discussion = new MdtDiscussion([
            'discussion_date' => $discussionDate,
            'age_snapshot' => $this->cell($row, $header, 'Age'),
            'weight_kg' => $weight,
            'oxygen_saturation' => $oxygenSaturation !== null ? (int) $oxygenSaturation : null,
            'diagnosis' => $diagnosis,
            'reason_for_discussion' => $reason,
            'history' => $this->cell($row, $header, 'History'),
            'exam_findings' => $this->cell($row, $header, 'Exam'),
            'echo_findings' => $this->cell($row, $header, 'Echo'),
            'cath_findings' => $this->cell($row, $header, 'Cath'),
            'specialist_fellow_id' => $specialist?->id,
            'discussion_results' => $this->cell($row, $header, 'Discussion Results'),
            'contact_number' => $this->cell($row, $header, 'Contact number'),
        ]);

        $childSummary = ["MDT Discussion: {$diagnosis} — {$discussionDate->format('d M Y')}"];

        return ImportRowResult::ok($raw, $patient, $isNew, [$discussion], $this->currentRowChanges, $childSummary);
    }
}
