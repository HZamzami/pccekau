<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;

// A Select drawn as one-click chips. Extending Select keeps its options
// handling (arrays, enums) and the value/values state SelectFilter expects.
class Chips extends Select
{
    protected string $view = 'filament.forms.components.chips';
}
