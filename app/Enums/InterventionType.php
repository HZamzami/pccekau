<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InterventionType: string implements HasColor, HasLabel
{
    case Surgery = 'surgery';
    case DiagnosticCath = 'diagnostic_cath';
    case InterventionalCathDevice = 'interventional_cath_device';
    case InterventionalCathBalloon = 'interventional_cath_balloon';
    case EpStudy = 'ep_study';
    case Ablation = 'ablation';
    case TransvenousPacing = 'transvenous_pacing_implant';
    case Other = 'other';

    // Legacy broad categories kept so existing rows still cast and display;
    // not offered when recording new interventions.
    case CathIntervention = 'cath_intervention';
    case EpProcedure = 'ep_procedure';

    public function getLabel(): string
    {
        return match ($this) {
            self::Surgery => 'Surgery',
            self::DiagnosticCath => 'Diagnostic cath',
            self::InterventionalCathDevice => 'Interventional cath (Device)',
            self::InterventionalCathBalloon => 'Interventional cath (Balloon)',
            self::EpStudy => 'Electrophysiology study',
            self::Ablation => 'Ablation',
            self::TransvenousPacing => 'Transvenous pacing system implant',
            self::Other => 'Other',
            self::CathIntervention => 'Cath intervention',
            self::EpProcedure => 'EP procedure',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Surgery => 'danger',
            self::DiagnosticCath => 'info',
            self::InterventionalCathDevice,
            self::InterventionalCathBalloon,
            self::CathIntervention => 'warning',
            self::EpStudy,
            self::Ablation,
            self::EpProcedure => 'success',
            self::TransvenousPacing => 'primary',
            self::Other => 'gray',
        };
    }

    public function isLegacy(): bool
    {
        return $this === self::CathIntervention || $this === self::EpProcedure;
    }

    /** @return array<string, string> value => label, excluding legacy categories */
    public static function selectableLabels(): array
    {
        return collect(self::cases())
            ->reject(fn (self $type) => $type->isLegacy())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])
            ->all();
    }
}
