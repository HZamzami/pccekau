<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CardiacLesion: string implements HasLabel
{
    case Vsd = 'vsd';
    case Asd = 'asd';
    case Avsd = 'avsd';
    case Pda = 'pda';
    case Tof = 'tof';
    case Tga = 'tga';
    case CcTga = 'cctga';
    case Hlhs = 'hlhs';
    case Coarctation = 'coarctation';
    case Iaa = 'iaa';
    case Tapvr = 'tapvr';
    case Papvr = 'papvr';
    case TruncusArteriosus = 'truncus_arteriosus';
    case TricuspidAtresia = 'tricuspid_atresia';
    case PulmonaryAtresiaIvs = 'pulmonary_atresia_ivs';
    case PulmonaryAtresiaVsd = 'pulmonary_atresia_vsd';
    case Ebstein = 'ebstein';
    case Dorv = 'dorv';
    case SingleVentricle = 'single_ventricle';
    case AorticStenosis = 'aortic_stenosis';
    case PulmonaryStenosis = 'pulmonary_stenosis';
    case MitralStenosis = 'mitral_stenosis';
    case MitralRegurgitation = 'mitral_regurgitation';
    case BicuspidAorticValve = 'bicuspid_aortic_valve';
    case VascularRing = 'vascular_ring';
    case Cardiomyopathy = 'cardiomyopathy';
    case Arrhythmia = 'arrhythmia';
    case Kawasaki = 'kawasaki';
    case PulmonaryHypertension = 'pulmonary_hypertension';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Vsd => 'VSD',
            self::Asd => 'ASD',
            self::Avsd => 'AVSD',
            self::Pda => 'PDA',
            self::Tof => 'TOF',
            self::Tga => 'TGA',
            self::CcTga => 'ccTGA',
            self::Hlhs => 'HLHS',
            self::Coarctation => 'Coarctation',
            self::Iaa => 'Interrupted aortic arch',
            self::Tapvr => 'TAPVR',
            self::Papvr => 'PAPVR',
            self::TruncusArteriosus => 'Truncus arteriosus',
            self::TricuspidAtresia => 'Tricuspid atresia',
            self::PulmonaryAtresiaIvs => 'PA-IVS',
            self::PulmonaryAtresiaVsd => 'PA-VSD',
            self::Ebstein => 'Ebstein anomaly',
            self::Dorv => 'DORV',
            self::SingleVentricle => 'Single ventricle',
            self::AorticStenosis => 'Aortic stenosis',
            self::PulmonaryStenosis => 'Pulmonary stenosis',
            self::MitralStenosis => 'Mitral stenosis',
            self::MitralRegurgitation => 'Mitral regurgitation',
            self::BicuspidAorticValve => 'Bicuspid aortic valve',
            self::VascularRing => 'Vascular ring',
            self::Cardiomyopathy => 'Cardiomyopathy',
            self::Arrhythmia => 'Arrhythmia',
            self::Kawasaki => 'Kawasaki disease',
            self::PulmonaryHypertension => 'Pulmonary hypertension',
            self::Other => 'Other',
        };
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $lesion) => [$lesion->value => $lesion->getLabel()])
            ->all();
    }
}
