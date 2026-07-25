<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImagingType: string implements HasLabel
{
    case Mri = 'mri';
    case Ct = 'ct';
    // Legacy: no longer offered on new reports (caths live in Interventions),
    // kept so existing rows still cast and display.
    case Cath = 'cath';
    case Echo = 'echo';
    case EchoTee = 'echo_tee';
    case Echo3d = 'echo_3d';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mri => 'MRI',
            self::Ct => 'CT',
            self::Cath => 'Cath',
            self::Echo => 'Echo (TTE)',
            self::EchoTee => 'Echo (TEE)',
            self::Echo3d => '3D Echo',
        };
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }

    /** @return array<string, string> value => label, excluding legacy types not offered on new reports */
    public static function selectableLabels(): array
    {
        return collect(self::cases())
            ->reject(fn (self $type) => $type === self::Cath)
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}
