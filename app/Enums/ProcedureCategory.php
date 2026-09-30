<?php

namespace App\Enums;

use App\Models\ProcedureBooking;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProcedureCategory: string implements HasColor, HasLabel
{
    case Surgery = 'surgery';
    case Cath = 'cath';
    case Ep = 'ep';
    case Ct = 'ct';
    case Mri = 'mri';
    case Echo = 'echo';
    case Tee = 'tee';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Surgery => 'Surgery (OR)',
            self::Cath => 'Cath',
            self::Ep => 'EP',
            self::Ct => 'CT',
            self::Mri => 'MRI',
            self::Echo => 'Echo (TTE)',
            self::Tee => 'TEE',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Surgery => 'danger',
            self::Cath => 'warning',
            self::Ep => 'success',
            self::Ct, self::Mri => 'info',
            self::Echo, self::Tee => 'primary',
            self::Other => 'gray',
        };
    }

    /** @return array<string> booking slot types this category can be booked into */
    public function slotTypes(): array
    {
        return match ($this) {
            self::Surgery => ['or'],
            self::Cath, self::Ep => ['cath_day_care', 'cath_inpatient'],
            self::Ct, self::Mri => ['mri_ct'],
            self::Echo, self::Tee => ['echo'],
            self::Other => array_keys(ProcedureBooking::$slotTypeLabels),
        };
    }

    public function defaultSlotType(): string
    {
        return $this->slotTypes()[0];
    }
}
