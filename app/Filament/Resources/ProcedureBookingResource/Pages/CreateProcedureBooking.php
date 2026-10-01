<?php

namespace App\Filament\Resources\ProcedureBookingResource\Pages;

use App\Filament\Resources\ProcedureBookingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class CreateProcedureBooking extends CreateRecord
{
    protected static string $resource = ProcedureBookingResource::class;

    // Validation catches taken slots; this covers two people booking the
    // same slot at the same moment.
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['data.slot_number' => 'This slot was just booked by someone else. Pick another slot or day.']);
        }
    }
}
