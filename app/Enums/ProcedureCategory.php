<?php

namespace App\Enums;

use App\Models\EpStudy;
use App\Models\ImagingReport;
use App\Models\Intervention;
use App\Models\MdtDiscussion;
use App\Models\ProcedureBooking;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProcedureCategory: string implements HasColor, HasLabel
{
    case Surgery = 'surgery';
    case Cath = 'cath';
    case Ep = 'ep';
    case Ecg = 'ecg';
    case Holter = 'holter';
    case StressEcg = 'stress_ecg';
    case Mri = 'mri';
    case Ct = 'ct';
    case Echo = 'echo';
    case Tee = 'tee';
    case Echo3d = 'echo_3d';
    case FetalEcho = 'fetal_echo';
    case CaseDiscussion = 'case_discussion';
    case Other = 'other';

    public const GROUP_LABELS = [
        'interventions' => 'Interventions',
        'imaging' => 'Imaging',
        'ep' => 'Electrophysiology',
        'case_discussion' => 'Case Discussions',
    ];

    /** The categories the team books most; listed first wherever categories are offered. */
    public const PRIMARY = [
        self::Surgery,
        self::Cath,
        self::Ep,
        self::Mri,
        self::Ct,
        self::Echo3d,
    ];

    public function getLabel(): string
    {
        return match ($this) {
            self::Surgery => 'Surgery',
            self::Cath => 'Cath',
            self::Ep => 'EP (invasive)',
            self::Ecg => 'ECG',
            self::Holter => 'Holter',
            self::StressEcg => 'Stress ECG',
            self::Mri => 'MRI',
            self::Ct => 'CT',
            self::Echo => 'Echo (TTE)',
            self::Tee => 'TEE',
            self::Echo3d => '3D Echo',
            self::FetalEcho => 'Fetal Echo',
            self::CaseDiscussion => 'Case discussion',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Surgery => 'danger',
            self::Cath => 'warning',
            self::Ep, self::Ecg, self::Holter, self::StressEcg => 'success',
            self::Ct, self::Mri => 'info',
            self::Echo, self::Tee, self::Echo3d, self::FetalEcho => 'primary',
            self::CaseDiscussion, self::Other => 'gray',
        };
    }

    /** Which list page / patient tab this category belongs to; null for Other. */
    public function group(): ?string
    {
        return match ($this) {
            self::Surgery, self::Cath => 'interventions',
            self::Mri, self::Ct, self::Echo, self::Tee, self::Echo3d, self::FetalEcho => 'imaging',
            self::Ep, self::Ecg, self::Holter, self::StressEcg => 'ep',
            self::CaseDiscussion => 'case_discussion',
            self::Other => null,
        };
    }

    /** @return array<string, string> value => label, limited to one group when given */
    public static function options(?string $group = null): array
    {
        return collect(self::cases())
            ->filter(fn (self $c) => ! $group || $c->group() === $group)
            ->sortBy(fn (self $c) => in_array($c, self::PRIMARY, true) ? array_search($c, self::PRIMARY, true) : count(self::PRIMARY))
            ->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])
            ->all();
    }

    /** @return array<string> booking slot types this category can be booked into; empty = no calendar slot */
    public function slotTypes(): array
    {
        return match ($this) {
            self::Surgery => ['or'],
            self::Cath, self::Ep => ['cath_day_care', 'cath_inpatient'],
            self::Ct, self::Mri => ['mri_ct'],
            self::Echo, self::Tee, self::Echo3d, self::FetalEcho => ['echo'],
            self::Ecg, self::Holter, self::StressEcg, self::CaseDiscussion => [],
            self::Other => array_keys(ProcedureBooking::$slotTypeLabels),
        };
    }

    public function defaultSlotType(): ?string
    {
        return $this->slotTypes()[0] ?? null;
    }

    /** @return class-string<Intervention|ImagingReport|EpStudy|MdtDiscussion>|null the patient-chart record a booking of this category creates */
    public function chartRecord(): ?string
    {
        return match ($this->group()) {
            'interventions' => Intervention::class,
            'imaging' => ImagingReport::class,
            'ep' => EpStudy::class,
            'case_discussion' => MdtDiscussion::class,
            default => null,
        };
    }

    /** @return array<InterventionType|EpStudyType> exact procedures the booking form offers for this category */
    public function exactProcedures(): array
    {
        return match ($this) {
            self::Surgery => [InterventionType::Surgery],
            self::Cath => [InterventionType::DiagnosticCath, InterventionType::InterventionalCathDevice, InterventionType::InterventionalCathBalloon],
            self::Ep => EpStudyType::invasive(),
            default => [],
        };
    }

    /** @return array<string, string> */
    public function exactProcedureOptions(): array
    {
        return collect($this->exactProcedures())->mapWithKeys(fn (InterventionType|EpStudyType $t) => [$t->value => $t->getLabel()])->all();
    }

    /** True when the booking must say which exact procedure it is. */
    public function needsExactProcedure(): bool
    {
        return count($this->exactProcedures()) > 1;
    }

    /** A booking's stored exact procedure (its intervention_type column), read as this category's type. */
    public function exactProcedure(?string $value): InterventionType|EpStudyType|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->group() === 'ep' ? EpStudyType::tryFrom($value) : InterventionType::tryFrom($value);
    }

    /** Used when a booking doesn't say which exact procedure it is (e.g. older bookings). */
    public function fallbackInterventionType(): ?InterventionType
    {
        return match ($this) {
            self::Surgery => InterventionType::Surgery,
            self::Cath => InterventionType::CathIntervention,
            default => null,
        };
    }

    public function imagingType(): ?ImagingType
    {
        return match ($this) {
            self::Mri => ImagingType::Mri,
            self::Ct => ImagingType::Ct,
            self::Echo => ImagingType::Echo,
            self::Tee => ImagingType::EchoTee,
            self::Echo3d => ImagingType::Echo3d,
            self::FetalEcho => ImagingType::FetalEcho,
            default => null,
        };
    }

    public function epStudyType(): ?EpStudyType
    {
        return match ($this) {
            self::Ecg => EpStudyType::Ecg,
            self::Holter => EpStudyType::Holter,
            self::StressEcg => EpStudyType::StressEcg,
            self::Ep => EpStudyType::EpStudy,
            default => null,
        };
    }

    /** Derive the booking category from an intervention type's clinical category string. */
    public static function fromInterventionCategory(string $category): ?self
    {
        return match ($category) {
            'surgery' => self::Surgery,
            'cath'    => self::Cath,
            'ep'      => self::Ep,
            default   => null,
        };
    }
}
