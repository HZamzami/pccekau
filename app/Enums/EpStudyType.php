<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EpStudyType: string implements HasLabel
{
    case Ecg = 'ecg';
    case Holter = 'holter';
    case StressEcg = 'stress_ecg';
    case EpStudy = 'ep_study';
    case Ablation = 'ablation';
    case TransvenousPacing = 'transvenous_pacing_implant';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ecg => 'ECG',
            self::Holter => 'Holter',
            self::StressEcg => 'Stress ECG',
            self::EpStudy => 'Electrophysiology study',
            self::Ablation => 'Ablation',
            self::TransvenousPacing => 'Transvenous pacing system implant',
        };
    }

    /** Invasive procedures are done in the cath lab and booked into its slots. */
    public function isInvasive(): bool
    {
        return in_array($this, [self::EpStudy, self::Ablation, self::TransvenousPacing], true);
    }

    /** @return array<self> */
    public static function invasive(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->isInvasive()));
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}
