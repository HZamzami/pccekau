<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;

// A Select drawn as one-click chips. Extending Select keeps its options
// handling (arrays, enums) and the value/values state SelectFilter expects.
// Quick chips sit straight in the filter bar; the rest open from a pill.
class Chips extends Select
{
    protected string $view = 'filament.forms.components.chips';

    protected bool $isQuick = false;

    public function quick(bool $condition = true): static
    {
        $this->isQuick = $condition;

        return $this;
    }

    public function isQuick(): bool
    {
        return $this->isQuick;
    }
}
