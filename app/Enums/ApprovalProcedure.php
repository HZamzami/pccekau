<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ApprovalProcedure: string implements HasLabel
{
    case Surgery = 'surgery';
    case DiagnosticCath = 'diagnostic_cath';
    case InterventionalCathDevice = 'interventional_cath_device';
    case InterventionalCathBalloon = 'interventional_cath_balloon';
    case EpStudy = 'ep_study';
    case Ablation = 'ablation';
    case TvPacingImplant = 'tv_pacing_implant';
    case CardiacCt = 'cardiac_ct';
    case CardiacMri = 'cardiac_mri';

    public function getLabel(): string
    {
        return match ($this) {
            self::Surgery                   => 'Surgery',
            self::DiagnosticCath            => 'Diagnostic Cath',
            self::InterventionalCathDevice  => 'Interventional Cath (Device)',
            self::InterventionalCathBalloon => 'Interventional Cath (Balloon)',
            self::EpStudy                   => 'Electrophysiology Study',
            self::Ablation                  => 'Ablation',
            self::TvPacingImplant           => 'Transvenous Pacing System Implant',
            self::CardiacCt                 => 'Cardiac CT',
            self::CardiacMri                => 'Cardiac MRI',
        };
    }
}
