<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProcedureStatus: string implements HasLabel, HasColor
{
    case Ordered = 'ordered';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case Postponed = 'postponed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ordered => 'Ordered',
            self::Confirmed => 'Confirmed',
            self::Done => 'Done',
            self::Postponed => 'Postponed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ordered => 'gray',
            self::Confirmed => 'info',
            self::Done => 'success',
            self::Postponed => 'danger',
        };
    }
}
