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
    case Other = 'other';

    /** Filtered views of the interventions list: category key => page title */
    public const CATEGORIES = [
        'surgery' => 'Surgery',
        'cath' => 'Cath',
    ];

    public function getLabel(): string
    {
        return match ($this) {
            self::Surgery => 'Surgery',
            self::DiagnosticCath => 'Diagnostic cath',
            self::InterventionalCathDevice => 'Interventional cath (Device)',
            self::InterventionalCathBalloon => 'Interventional cath (Balloon)',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Surgery => 'danger',
            self::DiagnosticCath => 'info',
            self::InterventionalCathDevice,
            self::InterventionalCathBalloon => 'warning',
            self::Other => 'gray',
        };
    }

    /** Key into CATEGORIES; null for Other, which only shows under "All procedures". */
    public function category(): ?string
    {
        return match ($this) {
            self::Surgery => 'surgery',
            self::DiagnosticCath,
            self::InterventionalCathDevice,
            self::InterventionalCathBalloon => 'cath',
            self::Other => null,
        };
    }

    /** @return array<self> */
    public static function ofCategory(string $category): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->category() === $category));
    }
}
