<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RvFunction: string implements HasLabel
{
    case Normal = 'normal';
    case MildlyReduced = 'mildly_reduced';
    case ModeratelyReduced = 'moderately_reduced';
    case SeverelyReduced = 'severely_reduced';

    public function getLabel(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::MildlyReduced => 'Mildly reduced',
            self::ModeratelyReduced => 'Moderately reduced',
            self::SeverelyReduced => 'Severely reduced',
        };
    }
}
