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
        if (strlen(trim($this->search)) < 2) {
            return collect();
        }

        return Patient::where('mrn', 'like', "%{$this->search}%")
            ->orWhere('name', 'like', "%{$this->search}%")
            ->limit(10)
            ->get();
    }
}
