<?php

namespace App\Filament\Resources\EpStudyResource\Pages;

use App\Enums\ReportStatus;
use App\Filament\Resources\EpStudyResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\EpStudy;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListEpStudies extends ListRecords
{
    protected static string $resource = EpStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WaitlistEntryResource::addAction('ep'),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $pending = [ReportStatus::Draft, ReportStatus::Preliminary];

        $tabs = [
            'pending' => Tab::make('Pending reads')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $pending))
                ->badge(EpStudy::whereIn('status', $pending)->count() ?: null)
                ->badgeColor('warning'),
        ];

        $staffId = auth()->user()?->staff?->id;

        if ($staffId !== null) {
            $tabs['my_reads'] = Tab::make('My reads')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $pending)->where('signed_by', $staffId))
                ->badge(EpStudy::whereIn('status', $pending)->where('signed_by', $staffId)->count() ?: null)
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
