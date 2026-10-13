<?php

namespace App\Filament\Resources\ImagingReportResource\Pages;

use App\Enums\ImagingType;
use App\Enums\ReportStatus;
use App\Filament\Resources\ImagingReportResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\ImagingReport;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListImagingReports extends ListRecords
{
    protected static string $resource = ImagingReportResource::class;

    /** Set by the modality sidebar items; null lists every modality. */
    #[Url]
    public ?string $modality = null;

    public function mount(): void
    {
        if (! array_key_exists((string) $this->modality, ImagingType::MODALITIES)) {
            $this->modality = null;
        }

        parent::mount();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->modality ? ImagingType::MODALITIES[$this->modality].' Reports' : parent::getTitle();
    }

    protected function getHeaderActions(): array
    {
        return [
            WaitlistEntryResource::addAction('imaging'),
            Actions\CreateAction::make()
                ->url(ImagingReportResource::getUrl('create', array_filter(['modality' => $this->modality]))),
        ];
    }

    /** Reports of the selected modality, for the tab counts and the table alike. */
    protected function scopeToModality(Builder $query): Builder
    {
        return $query->when($this->modality, fn (Builder $query, string $modality) => $query->whereIn('type', ImagingType::ofModality($modality)));
    }

    public function getTabs(): array
    {
        $pending = [ReportStatus::Draft, ReportStatus::Preliminary];

        $tabs = [
            'pending' => Tab::make('Pending reads')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $pending))
                ->badge($this->scopeToModality(ImagingReport::query())->whereIn('status', $pending)->count() ?: null)
                ->badgeColor('warning'),
        ];

        $staffId = auth()->user()?->staff?->id;

        if ($staffId !== null) {
            $tabs['my_reads'] = Tab::make('My reads')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', $pending)
                    ->whereHas('readers', fn (Builder $q) => $q->where('staff.id', $staffId)))
                ->badge($this->scopeToModality(ImagingReport::query())->whereIn('status', $pending)
                    ->whereHas('readers', fn (Builder $q) => $q->where('staff.id', $staffId))->count() ?: null)
                ->badgeColor('info');
        }

        $tabs['all'] = Tab::make('All');

        return $tabs;
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table)
            ->modifyQueryUsing(fn (Builder $query) => $this->scopeToModality($query));

        // The queue tabs surface the longest-waiting studies first
        if (in_array($this->activeTab, ['pending', 'my_reads'], true)) {
            $table->defaultSort('date', 'asc');
        }

        return $table;
    }
}
