<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WaitlistPriority: string implements HasLabel, HasColor
{
    case Routine = 'routine';
    case Urgent = 'urgent';

    public function getLabel(): string
    {
        return match ($this) {
            self::Routine => 'Routine',
            self::Urgent => 'Urgent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Routine => 'gray',
            self::Urgent => 'danger',
        };
    }
}
