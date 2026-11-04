<?php

namespace App\Filament\Tables\Filters;

use App\Filament\Forms\Components\Chips;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;

// The standard table filter: options as one-click chips, opened from a pill
// in the filter bar, or pinned straight into it with ->quick(). Several can
// be picked unless ->multiple(false) is set, in which case an "All" chip
// clears it.
class ChipFilter extends SelectFilter
{
    protected bool|Closure $isQuick = false;

    /** Pin this filter's chips straight into the bar: the page's main way to slice the list. */
    public function quick(bool|Closure $condition = true): static
    {
        $this->isQuick = $condition;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->multiple();
    }

    public function getFormField(): Select
    {
        // Relationship lists can be long; they keep the searchable select (in a pill).
        if ($this->queriesRelationships()) {
            return parent::getFormField()->hiddenLabel()->view('filament.forms.components.pill-select');
        }

        return Chips::make($this->isMultiple() ? 'values' : 'value')
            ->label($this->getLabel())
            ->multiple($this->isMultiple())
            ->quick((bool) $this->evaluate($this->isQuick))
            ->options($this->getOptions());
    }
}
