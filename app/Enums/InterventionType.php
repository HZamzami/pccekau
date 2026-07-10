<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InterventionType: string implements HasLabel, HasColor
{
    case Surgery = 'surgery';
    case CathIntervention = 'cath_intervention';
    case EpProcedure = 'ep_procedure';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Surgery => 'Surgery',
            self::CathIntervention => 'Cath intervention',
            self::EpProcedure => 'EP procedure',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Surgery => 'danger',
            self::CathIntervention => 'warning',
            self::EpProcedure => 'success',
            self::Other => 'gray',
        };
    }
}
