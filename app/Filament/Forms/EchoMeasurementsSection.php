<?php

namespace App\Filament\Forms;

use App\Enums\RegurgGrade;
use App\Enums\RvFunction;
use App\Models\Patient;
use App\Services\ZScoreService;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;

class EchoMeasurementsSection
{
    public static function make(): Section
    {
        return Section::make('Echo Measurements')
            ->description('Linear dimensions in cm — z-scores (Pettersen 2008) update as you type.')
            ->relationship('echoMeasurement')
            ->collapsible()
            ->visible(fn (Get $get) => in_array($get('type'), ['echo', 'echo_3d'], true))
            ->schema([
                Fieldset::make('Biometrics')->schema([
                    Grid::make(3)->columnSpanFull()->schema([
                        self::biometric('height_cm', 'Height (cm)', 20, 220),
                        self::biometric('weight_kg', 'Weight (kg)', 0.3, 200),

                        Placeholder::make('bsa_display')
                            ->label('BSA (m², Haycock)')
                            ->content(fn (Get $get) => self::bsa($get) ?? '—'),
                    ]),
                ]),

                Fieldset::make('Dimensions & z-scores')->schema([
                    Grid::make(4)->columnSpanFull()->schema([
                        self::zScored('ivsd', 'IVSd'),
                        self::zScored('lvidd', 'LVIDd'),
                        self::zScored('lvpwd', 'LVPWd'),
                        self::zScored('lvids', 'LVIDs'),
                        self::zScored('la', 'LA'),
                        self::zScored('ao_annulus', 'Ao annulus'),
                        self::zScored('ao_root', 'Ao root'),
                    ]),
                ]),

                Fieldset::make('Function')->schema([
                    Grid::make(4)->columnSpanFull()->schema([
                        TextInput::make('ef')->label('EF (%)')->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('fs')->label('FS (%)')->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('tapse')->label('TAPSE (cm)')->numeric()->minValue(0)->maxValue(5),
                        Select::make('rv_function')
                            ->label('RV function')
                            ->options(RvFunction::class),
                    ]),
                ]),

                Fieldset::make('Valves')->schema([
                    Grid::make(4)->columnSpanFull()->schema([
                        self::velocity('mv_peak_velocity', 'MV Vmax (m/s)'),
                        self::gradient('mv_peak_gradient', 'MV PG (mmHg)'),
                        self::gradient('mv_mean_gradient', 'MV mean (mmHg)'),
                        Select::make('mv_regurg')->label('MR grade')->options(RegurgGrade::class),

                        self::velocity('tv_peak_velocity', 'TV Vmax (m/s)'),
                        self::gradient('tv_peak_gradient', 'TV PG (mmHg)'),
                        Select::make('tv_regurg')->label('TR grade')->options(RegurgGrade::class),

                        self::velocity('av_peak_velocity', 'AV Vmax (m/s)'),
                        self::gradient('av_peak_gradient', 'AV PG (mmHg)'),
                        self::gradient('av_mean_gradient', 'AV mean (mmHg)'),
                        Select::make('av_regurg')->label('AI grade')->options(RegurgGrade::class),

                        self::velocity('pv_peak_velocity', 'PV Vmax (m/s)'),
                        self::gradient('pv_peak_gradient', 'PV PG (mmHg)'),
                        Select::make('pv_regurg')->label('PI grade')->options(RegurgGrade::class),
                    ]),
                ]),

                Fieldset::make('Arch & shunts')->schema([
                    Grid::make(3)->columnSpanFull()->schema([
                        self::gradient('coarct_peak_gradient', 'Coarctation PG (mmHg)'),
                        self::gradient('coarct_mean_gradient', 'Coarctation mean (mmHg)'),
                        TextInput::make('pda_size_mm')->label('PDA size (mm)')->numeric()->minValue(0)->maxValue(20),
                    ]),
                ]),
            ]);
    }

    private static function biometric(string $key, string $label, float $min, float $max): TextInput
    {
        return TextInput::make($key)
            ->label($label)
            ->numeric()
            ->minValue($min)
            ->maxValue($max)
            ->live(debounce: 500)
            // Prefill from the patient's baseline: the resource form exposes
            // patient_id (flat state), the relation manager knows its owner.
            ->afterStateHydrated(function (TextInput $component, $state, Get $get, $livewire) use ($key) {
                if (filled($state)) {
                    return;
                }

                $patient = $livewire instanceof RelationManager
                    ? $livewire->getOwnerRecord()
                    : Patient::find($get('patient_id'));

                if ($patient?->{$key} !== null) {
                    $component->state($patient->{$key});
                }
            });
    }

    private static function velocity(string $key, string $label): TextInput
    {
        return TextInput::make($key)->label($label)->numeric()->minValue(0)->maxValue(10);
    }

    private static function gradient(string $key, string $label): TextInput
    {
        return TextInput::make($key)->label($label)->numeric()->minValue(0)->maxValue(200);
    }

    private static function bsa(Get $get): ?float
    {
        return ZScoreService::bsaHaycock((float) $get('height_cm'), (float) $get('weight_kg'));
    }

    private static function zScored(string $key, string $label): TextInput
    {
        $z = function (Get $get, $state) use ($key): ?float {
            $bsa = self::bsa($get);

            if ($bsa === null || ! is_numeric($state) || (float) $state <= 0) {
                return null;
            }

            return ZScoreService::zScore($key, (float) $state, $bsa);
        };

        return TextInput::make($key)
            ->label("{$label} (cm)")
            ->numeric()
            ->minValue(0)
            ->maxValue(15)
            ->live(debounce: 500)
            ->hint(fn (Get $get, $state) => ($zv = $z($get, $state)) !== null ? "z = {$zv}" : null)
            ->hintColor(fn (Get $get, $state) => abs($z($get, $state) ?? 0) > 2 ? 'danger' : 'gray');
    }
}
