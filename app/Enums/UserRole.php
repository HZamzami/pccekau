<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin  => 'Admin',
            self::Doctor => 'Doctor',
            self::Viewer => 'Viewer',
        };
    }
}
