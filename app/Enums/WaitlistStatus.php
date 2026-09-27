<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WaitlistStatus: string implements HasLabel, HasColor
{
    case Waiting = 'waiting';
    case Scheduled = 'scheduled';
    case Removed = 'removed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting',
            self::Scheduled => 'Scheduled',
            self::Removed => 'Removed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Waiting => 'warning',
            self::Scheduled => 'success',
            self::Removed => 'gray',
        };
    }
}
