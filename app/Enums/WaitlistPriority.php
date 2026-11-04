<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

// 1 is the most urgent, 5 the least; the wait-list sorts on this.
enum WaitlistPriority: int implements HasLabel, HasColor
{
    case P1 = 1;
    case P2 = 2;
    case P3 = 3;
    case P4 = 4;
    case P5 = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::P1 => '1 — Most urgent',
            self::P5 => '5 — Least urgent',
            default => (string) $this->value,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::P1 => 'danger',
            self::P2 => 'warning',
            self::P3 => 'primary',
            self::P4, self::P5 => 'gray',
        };
    }
}
