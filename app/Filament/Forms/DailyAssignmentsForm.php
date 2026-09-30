<?php

namespace App\Filament\Forms;

use App\Enums\CoverageRole;
use Closure;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;

class DailyAssignmentsForm
{
    /**
     * One row per role: a "Whole week" picker that fills all 7 days, then Sun–Sat.
     * Bound to `assignments.<role>.<day>`; see HasDailyAssignments::syncAssignments().
     *
     * @param  array<CoverageRole>  $roles
     * @param  Closure(): array<int, string>  $staffOptions
     * @return array<Fieldset>
     */
    public static function schema(array $roles, Closure $staffOptions): array
    {
        $cache = null;
        $options = function () use (&$cache, $staffOptions) {
            return $cache ??= $staffOptions();
        };

        return array_map(fn (CoverageRole $role) => Fieldset::make($role->getLabel())
            ->schema([
                Grid::make(['default' => 2, 'md' => 4, 'xl' => 8])->schema([
                    Select::make("fill_week.{$role->value}")
                        ->label('Whole week')
                        ->placeholder('Set all 7 days…')
                        ->options($options)
                        ->searchable()
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) use ($role) {
                            if (! $state) {
                                return;
                            }

                            foreach (range(0, 6) as $day) {
                                $set("assignments.{$role->value}.{$day}", $state);
                            }

                            $set("fill_week.{$role->value}", null);
                        }),

                    ...array_map(fn (int $day) => Select::make("assignments.{$role->value}.{$day}")
                        ->label(CoverageRole::DAYS[$day])
                        ->options($options)
                        ->searchable()
                        ->nullable(), range(0, 6)),
                ]),
            ]), $roles);
    }
}
