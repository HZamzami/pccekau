<?php

namespace App\Filament\Resources\ProcedureBookingResource\Pages;

use App\Filament\Resources\ProcedureBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcedureBooking extends EditRecord
{
    protected static string $resource = ProcedureBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
