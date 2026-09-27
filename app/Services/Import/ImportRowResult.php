<?php

namespace App\Services\Import;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;

final class ImportRowResult
{
    /**
     * @param array<string, mixed> $raw
     * @param array<Model> $childRecords Unsaved child records (Intervention/EpStudy/MdtDiscussion) to persist alongside the patient.
     * @param array<int, array{model: string, field: string, label: string, value: mixed}> $fieldChanges Every field fillIfBlank() actually set — powers the preview diff panel.
     * @param array<int, string> $childSummary One human-readable line per child record being created.
     */
    public function __construct(
        public readonly array $raw,
        public readonly ?Patient $patient,
        public readonly bool $isNewPatient,
        public readonly array $childRecords,
        public readonly string $status,
        public readonly ?string $error = null,
        public readonly array $fieldChanges = [],
        public readonly array $childSummary = [],
    ) {
    }

    public static function error(array $raw, string $message): self
    {
        return new self($raw, null, false, [], 'error', $message);
    }

    public static function ok(
        array $raw,
        Patient $patient,
        bool $isNewPatient,
        array $childRecords,
        array $fieldChanges = [],
        array $childSummary = [],
    ): self {
        return new self($raw, $patient, $isNewPatient, $childRecords, $isNewPatient ? 'new' : 'update', null, $fieldChanges, $childSummary);
    }

    public function summaryLabel(): string
    {
        $mrn = $this->raw['mrn'] ?? '—';
        $name = $this->raw['name'] ?? '—';

        return "{$mrn} — {$name}";
    }

    /** Short one-line summary of what this row will actually do, for the collapsed table row. */
    public function changeSummary(): string
    {
        if ($this->status === 'error') {
            return $this->error ?? 'Cannot be imported';
        }

        $parts = [];

        if ($this->isNewPatient) {
            $parts[] = 'New patient';
        } elseif (count($this->fieldChanges) > 0) {
            $fields = collect($this->fieldChanges)->pluck('label')->implode(', ');
            $parts[] = "Fills in: {$fields}";
        } else {
            $parts[] = 'No new patient fields';
        }

        foreach ($this->childSummary as $line) {
            $parts[] = $line;
        }

        return implode(' · ', $parts);
    }
}
