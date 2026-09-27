<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Nurse = 'nurse';
    case FrontDesk = 'front_desk';
    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin     => 'Admin',
            self::Doctor    => 'Doctor',
            self::Nurse     => 'Nurse',
            self::FrontDesk => 'Front Desk',
            self::Viewer    => 'Viewer',
        };
    }
}
