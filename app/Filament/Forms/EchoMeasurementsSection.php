<?php

namespace App\Filament\Forms;

use App\Services\ZScoreService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;

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
                Grid::make(3)->schema([
                    TextInput::make('height_cm')
                        ->label('Height (cm)')
                        ->numeric()
                        ->live(debounce: 500),

                    TextInput::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric()
                        ->live(debounce: 500),

                    Placeholder::make('bsa_display')
                        ->label('BSA (m², Haycock)')
                        ->content(fn (Get $get) => self::bsa($get) ?? '—'),
                ]),

                Grid::make(4)->schema([
                    self::zScored('ivsd', 'IVSd'),
                    self::zScored('lvidd', 'LVIDd'),
                    self::zScored('lvpwd', 'LVPWd'),
                    self::zScored('lvids', 'LVIDs'),
                    self::zScored('la', 'LA'),
                    self::zScored('ao_annulus', 'Ao annulus'),
                    self::zScored('ao_root', 'Ao root'),
                ]),

                Grid::make(4)->schema([
                    TextInput::make('ef')->label('EF (%)')->numeric(),
                    TextInput::make('fs')->label('FS (%)')->numeric(),
                ]),

                Grid::make(4)->schema([
                    TextInput::make('mv_peak_velocity')->label('MV Vmax (m/s)')->numeric(),
                    TextInput::make('mv_peak_gradient')->label('MV PG (mmHg)')->numeric(),
                    TextInput::make('tv_peak_velocity')->label('TV Vmax (m/s)')->numeric(),
                    TextInput::make('tv_peak_gradient')->label('TV PG (mmHg)')->numeric(),
                    TextInput::make('pv_peak_velocity')->label('PV Vmax (m/s)')->numeric(),
                    TextInput::make('pv_peak_gradient')->label('PV PG (mmHg)')->numeric(),
                    TextInput::make('av_peak_velocity')->label('AV Vmax (m/s)')->numeric(),
                    TextInput::make('av_peak_gradient')->label('AV PG (mmHg)')->numeric(),
                ]),
            ]);
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
            ->live(debounce: 500)
            ->hint(fn (Get $get, $state) => ($zv = $z($get, $state)) !== null ? "z = {$zv}" : null)
            ->hintColor(fn (Get $get, $state) => abs($z($get, $state) ?? 0) > 2 ? 'danger' : 'gray');
    }
}
