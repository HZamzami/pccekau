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

    // Legacy broad category kept so existing rows still cast and display;
    // not offered when recording new interventions.
    case CathIntervention = 'cath_intervention';

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
            self::CathIntervention => 'Cath intervention',
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
            self::InterventionalCathBalloon,
            self::CathIntervention => 'cath',
            self::Other => null,
        };
    }

    /** @return array<self> */
    public static function ofCategory(string $category): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->category() === $category));
    }

    public function isLegacy(): bool
    {
        return $this === self::CathIntervention;
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
