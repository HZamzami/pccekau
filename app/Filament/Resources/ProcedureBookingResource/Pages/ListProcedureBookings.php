<?php

namespace App\Filament\Resources\ProcedureBookingResource\Pages;

use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcedureBookings extends ListRecords
{
    protected static string $resource = ProcedureBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WaitlistEntryResource::addAction(),
            Actions\CreateAction::make(),
        ];
    }
}
