<?php

namespace App\Filament\Resources\ProcedureBookingResource\Pages;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\ProcedureBooking;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListProcedureBookings extends ListRecords
{
    protected static string $resource = ProcedureBookingResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'All Bookings';
    }

    protected function getHeaderActions(): array
    {
        return [
            WaitlistEntryResource::addAction(),
            Actions\CreateAction::make(),
        ];
    }

    /** One tab per main procedure type, each badged with its upcoming scheduled bookings. */
    public function getTabs(): array
    {
        $upcoming = ProcedureBooking::where('procedure_status', ProcedureStatus::Scheduled)
            ->whereDate('booking_date', '>=', today())
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $tabs = ['all' => Tab::make('All')];

        foreach (ProcedureCategory::TAB_CATEGORIES as $category) {
            $tabs[$category->value] = Tab::make($category->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', $category))
                ->badge($upcoming[$category->value] ?? null);
        }

        return $tabs;
    }
}
