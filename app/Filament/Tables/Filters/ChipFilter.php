<?php

namespace App\Filament\Tables\Filters;

use App\Filament\Forms\Components\Chips;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;

// The standard table filter: every option visible as a chip, applied on
// click. Several can be picked unless ->multiple(false) is set, in which
// case an "All" chip clears it.
class ChipFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->multiple();
        $this->columnSpanFull();
    }

    public function getFormField(): Select
    {
        // Relationship lists can be long; they keep the searchable select.
        if ($this->queriesRelationships()) {
            return parent::getFormField();
        }

        return Chips::make($this->isMultiple() ? 'values' : 'value')
            ->label($this->getLabel())
            ->multiple($this->isMultiple())
            ->options($this->getOptions());
    }
}
