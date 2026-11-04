<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;
use Illuminate\Support\Arr;

// The short text a filter pill shows once it's set: "Surgery, Cath +2".
class FilterSummary
{
    /** @param  array<int, string>  $labels */
    public static function labels(array $labels): ?string
    {
        $labels = array_values(array_filter($labels, 'filled'));

        if ($labels === []) {
            return null;
        }

        $shown = implode(', ', array_slice($labels, 0, 2));

        return count($labels) > 2 ? $shown.' +'.(count($labels) - 2) : $shown;
    }

    /** Labels of the chosen options, for plain or relationship-backed selects. */
    public static function forSelect(Select $field): ?string
    {
        $state = $field->getState();

        if (blank($state) || $state === []) {
            return null;
        }

        if ($field->getRelationship() !== null) {
            return static::labels($field->isMultiple() ? $field->getOptionLabels() : [$field->getOptionLabel()]);
        }

        $options = $field->getOptions();

        return static::labels(array_map(fn ($value) => (string) ($options[$value] ?? $value), Arr::wrap($state)));
    }
}
