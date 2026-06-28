<?php

namespace App\Filament\Widgets;

use App\Models\OncallSchedule;
use Filament\Widgets\Widget;

class TodayOncallWidget extends Widget
{
    protected static string $view = 'filament.widgets.today-oncall-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getSchedule(): ?OncallSchedule
    {
        return OncallSchedule::currentWeek();
    }

    public function getTodayLabel(): string
    {
        return now()->format('l');
    }
}
