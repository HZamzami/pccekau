<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CoverageRole: string implements HasLabel
{
    case Clinic = 'clinic';
    case Inpatient = 'inpatient';
    case Consultation = 'consultation';
    case Cath = 'cath';
    case Oncall = 'oncall';

    case ConsultantService = 'consultant_service';
    case ConsultantCath = 'consultant_cath';
    case ConsultantEp = 'consultant_ep';

    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function getLabel(): string
    {
        return match ($this) {
            self::Clinic => 'Clinic',
            self::Inpatient => 'Inpatient',
            self::Consultation => 'Consultation',
            self::Cath, self::ConsultantCath => 'Cath',
            self::Oncall => 'On-Call',
            self::ConsultantService => 'Service',
            self::ConsultantEp => 'EP',
        };
    }

    public function calendarSummary(): string
    {
        return match ($this) {
            self::Oncall => 'On-Call',
            self::ConsultantService => 'Service Consultant',
            self::ConsultantCath => 'Cath Consultant',
            self::ConsultantEp => 'EP Consultant',
            default => "{$this->getLabel()} Coverage",
        };
    }

    /** @return array<self> */
    public static function forCoverage(): array
    {
        return [self::Clinic, self::Inpatient, self::Consultation, self::Cath, self::Oncall];
    }

    /** @return array<self> */
    public static function forConsultants(): array
    {
        return [self::ConsultantService, self::ConsultantCath, self::ConsultantEp];
    }
}
