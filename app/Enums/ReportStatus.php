<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case Preliminary = 'preliminary';
    case Final = 'final';
    case Amended = 'amended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft       => 'Draft',
            self::Preliminary => 'Preliminary',
            self::Final       => 'Final',
            self::Amended     => 'Amended',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft       => 'gray',
            self::Preliminary => 'warning',
            self::Final       => 'success',
            self::Amended     => 'danger',
        };
    }

    public function isLocked(): bool
    {
        return $this === self::Final;
    }
}
