<?php

namespace App\Services\Import;

use App\Enums\PatientStatus;
use App\Models\MdtDiscussion;
use App\Services\Import\Concerns\ParsesImportValues;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CasesDiscussionImporter
{
    use ParsesImportValues;

    /** @return Collection<int, ImportRowResult> */
    public function preview(string $filePath): Collection
    {
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
        } else {
            $this->fillIfBlank($patient, 'name', $name);
        }

        $this->fillIfBlank($patient, 'gender', strtolower((string) $this->cell($row, $header, 'Gender')) ?: null);
        $this->fillIfBlank($patient, 'nationality', $this->cell($row, $header, 'Nationality'));
        $this->fillIfBlank($patient, 'referring_physician', $this->cell($row, $header, 'Primary'));
        $this->fillIfBlank($patient, 'weight_kg', $this->cell($row, $header, 'Weight'));
        $this->fillIfBlank($patient, 'contact_number', $this->cell($row, $header, 'Contact number'));

        $specialist = $this->matchStaffByName($this->cell($row, $header, 'Specialist/Fellow'));

        $discussion = new MdtDiscussion([
            'discussion_date' => $discussionDate,
            'age_snapshot' => $this->cell($row, $header, 'Age'),
            'weight_kg' => $this->cell($row, $header, 'Weight'),
            'oxygen_saturation' => $this->cell($row, $header, 'Oxygen saturation'),
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

        return ImportRowResult::ok($raw, $patient, $isNew, [$discussion]);
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
