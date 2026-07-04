<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PatientStatus: string implements HasLabel, HasColor
{
    case Active = 'active';
    case FollowUp = 'follow-up';
    case PostOp = 'post-op';
    case Discharged = 'discharged';
    case Deceased = 'deceased';
    case Transferred = 'transferred';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::FollowUp => 'Follow-Up',
            self::PostOp => 'Post-Op',
            self::Discharged => 'Discharged',
            self::Deceased => 'Deceased',
            self::Transferred => 'Transferred',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::FollowUp => 'warning',
            self::PostOp => 'info',
            self::Discharged => 'gray',
            self::Deceased => 'danger',
            self::Transferred => 'gray',
        };
    }

    /** Statuses excluded from follow-up queues and reminders. */
    public static function inactiveValues(): array
    {
        return [self::Deceased->value, self::Transferred->value];
    }
}
