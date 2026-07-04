<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RegurgGrade: string implements HasLabel
{
    case None = 'none';
    case Trivial = 'trivial';
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }
}
