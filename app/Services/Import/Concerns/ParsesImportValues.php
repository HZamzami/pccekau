<?php

namespace App\Services\Import\Concerns;

use App\Models\Patient;
use Illuminate\Support\Carbon;

trait ParsesImportValues
{
    /**
     * Patients resolved so far in the current preview() run, keyed by MRN.
     * Without this, a file with the same MRN on multiple rows (e.g. a
     * patient with several Holter sessions) would build a separate unsaved
     * Patient per row, and every occurrence after the first would fail its
     * unique(clinic_id, mrn) insert during commit().
     *
     * @var array<string, Patient>
     */
    protected array $batchPatients = [];

    protected function normalizeMrn(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        // Spreadsheet MRN columns are often read as floats (e.g. "1201931.0").
        if (is_numeric($value) && str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value !== '' ? $value : null;
    }

    /**
     * Spreadsheets in this practice mix "d/m/Y", "d-m-Y", and native Excel
     * date cells (already formatted to a string by PhpSpreadsheet's
     * toArray($formatData: true)). Day-first is assumed for ambiguous
     * slash/dash formats since these sheets are all Saudi-locale.
     */
    protected function parseFlexibleDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->startOfDay();
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'd/m/y', 'd-m-y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Pulls the first number out of free text like "29 kg", "3.8kg", or a
     * range like "90-92%" (ranges take the first value — a simplification,
     * not a clinical judgement call). Returns null if no number is found.
     */
    protected function parseNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (preg_match('/-?\d+(\.\d+)?/', (string) $value, $matches)) {
            return (float) $matches[0];
        }

        return null;
    }

    protected function blank(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /** Sets $model->$attribute = $value only if the model doesn't already have a value there. */
    protected function fillIfBlank($model, string $attribute, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->blank($model->{$attribute})) {
            $model->{$attribute} = $value;
        }
    }

    /**
     * Finds an existing patient by MRN (scoped to the current tenant via the
     * global clinic scope), or a new unsaved instance — reusing the same
     * instance across rows within one import batch (see $batchPatients).
     */
    protected function findOrNewPatient(string $mrn): array
    {
        if (isset($this->batchPatients[$mrn])) {
            return [$this->batchPatients[$mrn], false];
        }

        $patient = Patient::where('mrn', $mrn)->first();
        $isNew = false;

        if (! $patient) {
            $patient = new Patient(['mrn' => $mrn]);
            $isNew = true;
        }

        $this->batchPatients[$mrn] = $patient;

        return [$patient, $isNew];
    }

    /** @return array<string, int> lowercased, trimmed header text => column index */
    protected function mapHeaders(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $header) {
            if ($header === null || trim((string) $header) === '') {
                continue;
            }

            $map[strtolower(trim((string) $header))] = $index;
        }

        return $map;
    }

    protected function cell(array $row, array $headerMap, string $header): mixed
    {
        $key = strtolower(trim($header));

        if (! isset($headerMap[$key])) {
            return null;
        }

        $value = $row[$headerMap[$key]] ?? null;

        return is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
    }

    /** Best-effort match against Staff.name for free-text like "D. Zaher" / "Dr. Khadijah". */
    protected function matchStaffByName(?string $text): ?\App\Models\Staff
    {
        if ($this->blank($text)) {
            return null;
        }

        $cleaned = preg_replace('/\b(dr\.?|d\.?)\b/i', '', $text);
        $words = array_filter(preg_split('/[\s,\/]+/', trim((string) $cleaned)), fn ($w) => mb_strlen($w) >= 3);

        foreach ($words as $word) {
            $match = \App\Models\Staff::where('name', 'like', "%{$word}%")->first();

            if ($match) {
                return $match;
            }
        }

        return null;
    }
}
