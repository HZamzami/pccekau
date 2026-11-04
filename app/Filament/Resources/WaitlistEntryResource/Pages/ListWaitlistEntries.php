<?php

namespace App\Filament\Resources\WaitlistEntryResource\Pages;

use App\Enums\ProcedureCategory;
use App\Enums\WaitlistStatus;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\WaitlistEntry;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWaitlistEntries extends ListRecords
{
    protected static string $resource = WaitlistEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /** One tab per main procedure type, each badged with how many are still waiting. */
    public function getTabs(): array
    {
        $waiting = WaitlistEntry::where('status', WaitlistStatus::Waiting)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $tabs = ['all' => Tab::make('All')->badge($waiting->sum() ?: null)];

        foreach (ProcedureCategory::TAB_CATEGORIES as $category) {
            $tabs[$category->value] = Tab::make($category->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', $category))
                ->badge($waiting[$category->value] ?? null);
        }

        return $tabs;
    }
}
