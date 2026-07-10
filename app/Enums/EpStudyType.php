<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EpStudyType: string implements HasLabel
{
    case Holter = 'holter';
    case StressEcg = 'stress_ecg';

    public function getLabel(): string
    {
        return match ($this) {
            self::Holter    => 'Holter',
            self::StressEcg => 'Stress ECG',
        };
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}
