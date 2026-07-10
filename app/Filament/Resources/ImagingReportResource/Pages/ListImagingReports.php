<?php

namespace App\Filament\Resources\ImagingReportResource\Pages;

use App\Enums\ReportStatus;
use App\Filament\Resources\ImagingReportResource;
use App\Models\ImagingReport;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListImagingReports extends ListRecords
{
    protected static string $resource = ImagingReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $pending = [ReportStatus::Draft, ReportStatus::Preliminary];

        $tabs = [
            'pending' => Tab::make('Pending reads')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $pending))
                ->badge(ImagingReport::whereIn('status', $pending)->count() ?: null)
                ->badgeColor('warning'),
        ];

        $staffId = auth()->user()?->staff?->id;

        if ($staffId !== null) {
            $tabs['my_reads'] = Tab::make('My reads')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $pending)->where('signed_by', $staffId))
                ->badge(ImagingReport::whereIn('status', $pending)->where('signed_by', $staffId)->count() ?: null)
                ->badgeColor('info');
        }

        $tabs['all'] = Tab::make('All');

        return $tabs;
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        // The queue tabs surface the longest-waiting studies first
        if (in_array($this->activeTab, ['pending', 'my_reads'], true)) {
            $table->defaultSort('date', 'asc');
        }

        return $table;
    }
}
