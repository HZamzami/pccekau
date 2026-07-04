<?php

namespace App\Filament\Widgets;

use App\Models\Patient;
use Filament\Widgets\Widget;

class PatientSearchWidget extends Widget
{
    protected static string $view = 'filament.widgets.patient-search-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public string $search = '';

    public function getResults(): \Illuminate\Support\Collection
    {
        $term = trim($this->search);

        if (strlen($term) < 2) {
            return collect();
        }

        // Escape LIKE wildcards so "%" or "_" in the input match literally
        $term = addcslashes($term, '%_\\');

        return Patient::where('mrn', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->limit(10)
            ->get();
    }
}
