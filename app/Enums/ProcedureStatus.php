<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProcedureStatus: string implements HasLabel, HasColor
{
    case Requested = 'ordered';
    case Scheduled = 'confirmed';
    case Completed = 'done';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Postponed => 'Postponed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'gray',
            self::Scheduled => 'info',
            self::Completed => 'success',
            self::Postponed => 'danger',
            self::Cancelled => 'warning',
        };
    }
}
