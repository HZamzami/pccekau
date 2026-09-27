<?php

namespace App\Services\Import;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;

final class ImportRowResult
{
    /**
     * @param array<string, mixed> $raw
     * @param array<Model> $childRecords Unsaved child records (Intervention/EpStudy/MdtDiscussion) to persist alongside the patient.
     */
    public function __construct(
        public readonly array $raw,
        public readonly ?Patient $patient,
        public readonly bool $isNewPatient,
        public readonly array $childRecords,
        public readonly string $status,
        public readonly ?string $error = null,
    ) {
    }

    public static function error(array $raw, string $message): self
    {
        return new self($raw, null, false, [], 'error', $message);
    }

    public static function ok(array $raw, Patient $patient, bool $isNewPatient, array $childRecords): self
    {
        return new self($raw, $patient, $isNewPatient, $childRecords, $isNewPatient ? 'new' : 'update');
    }

    public function summaryLabel(): string
    {
        $mrn = $this->raw['mrn'] ?? '—';
        $name = $this->raw['name'] ?? '—';

        return "{$mrn} — {$name}";
    }
}
