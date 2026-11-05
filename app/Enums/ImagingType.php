<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImagingType: string implements HasLabel
{
    case Mri = 'mri';
    case Ct = 'ct';
    case Echo = 'echo';
    case EchoTee = 'echo_tee';
    case Echo3d = 'echo_3d';
    case FetalEcho = 'fetal_echo';

    /** Sidebar sub-headings of Imaging Reports: modality key => label */
    public const MODALITIES = [
        'echo' => 'Echo',
        'fetal_echo' => 'Fetal Echo',
        'mri' => 'MRI',
        'ct' => 'CT',
    ];

    public function getLabel(): string
    {
        return match ($this) {
            self::Mri => 'MRI',
            self::Ct => 'CT',
            self::Echo => 'Echo (TTE)',
            self::EchoTee => 'Echo (TEE)',
            self::Echo3d => '3D Echo',
            self::FetalEcho => 'Fetal Echo',
        };
    }

    /** Key into MODALITIES. */
    public function modality(): string
    {
        return match ($this) {
            self::Echo, self::EchoTee, self::Echo3d => 'echo',
            self::FetalEcho => 'fetal_echo',
            self::Mri => 'mri',
            self::Ct => 'ct',
        };
    }

    /** @return array<self> */
    public static function ofModality(string $modality): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->modality() === $modality));
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}
