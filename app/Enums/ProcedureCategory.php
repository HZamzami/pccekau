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
    case CaseDiscussion = 'case_discussion';
    case Other = 'other';

    public const GROUP_LABELS = [
        'interventions' => 'Interventions',
        'imaging' => 'Imaging',
        'ep' => 'Electrophysiology',
        'case_discussion' => 'Case Discussions',
    ];

    public function getLabel(): string
    {
        return match ($this) {
            self::Surgery => 'Surgery (OR)',
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
            self::Echo, self::Tee, self::Echo3d => 'primary',
            self::CaseDiscussion, self::Other => 'gray',
        };
    }

    /** Which list page / patient tab this category belongs to; null for Other. */
    public function group(): ?string
    {
        return match ($this) {
            self::Surgery, self::Cath, self::Ep => 'interventions',
            self::Mri, self::Ct, self::Echo, self::Tee, self::Echo3d => 'imaging',
            self::Ecg, self::Holter, self::StressEcg => 'ep',
            self::CaseDiscussion => 'case_discussion',
            self::Other => null,
        };
    }

    /** @return array<string, string> value => label, limited to one group when given */
    public static function options(?string $group = null): array
    {
        return collect(self::cases())
            ->filter(fn (self $c) => ! $group || $c->group() === $group)
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
            self::Echo, self::Tee, self::Echo3d => ['echo'],
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

    /** @return array<InterventionType> exact procedures the booking form offers for this category */
    public function interventionTypes(): array
    {
        return match ($this) {
            self::Surgery => [InterventionType::Surgery],
            self::Cath => [InterventionType::DiagnosticCath, InterventionType::InterventionalCathDevice, InterventionType::InterventionalCathBalloon],
            self::Ep => [InterventionType::EpStudy, InterventionType::Ablation, InterventionType::TransvenousPacing],
            default => [],
        };
    }

    /** @return array<string, string> */
    public function interventionTypeOptions(): array
    {
        return collect($this->interventionTypes())->mapWithKeys(fn (InterventionType $t) => [$t->value => $t->getLabel()])->all();
    }

    /** True when the booking must say which exact procedure it is. */
    public function needsInterventionType(): bool
    {
        return count($this->interventionTypes()) > 1;
    }

    /** Used when a booking doesn't say which exact procedure it is (e.g. older bookings). */
    public function fallbackInterventionType(): ?InterventionType
    {
        return match ($this) {
            self::Surgery => InterventionType::Surgery,
            self::Cath => InterventionType::CathIntervention,
            self::Ep => InterventionType::EpProcedure,
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
            default => null,
        };
    }

    public function epStudyType(): ?EpStudyType
    {
        return match ($this) {
            self::Ecg => EpStudyType::Ecg,
            self::Holter => EpStudyType::Holter,
            self::StressEcg => EpStudyType::StressEcg,
            default => null,
        };
    }
}
