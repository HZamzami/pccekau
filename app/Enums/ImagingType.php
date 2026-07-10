<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImagingType: string implements HasLabel
{
    case Mri = 'mri';
    case Ct = 'ct';
    case Cath = 'cath';
    case Echo = 'echo';
    case Echo3d = 'echo_3d';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mri    => 'MRI',
            self::Ct     => 'CT',
            self::Cath   => 'Cath',
            self::Echo   => 'Echo (TTE)',
            self::Echo3d => '3D Echo',
        };
    }

    public function isEcho(): bool
    {
        return $this === self::Echo || $this === self::Echo3d;
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}
