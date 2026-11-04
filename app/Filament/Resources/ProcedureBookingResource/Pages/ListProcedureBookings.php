<?php

namespace App\Filament\Resources\ProcedureBookingResource\Pages;

use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

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
}
