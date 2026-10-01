<?php

namespace App\Filament\Resources\ProcedureBookingResource\Pages;

use App\Filament\Resources\ProcedureBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class EditProcedureBooking extends EditRecord
{
    protected static string $resource = ProcedureBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['data.slot_number' => 'This slot was just booked by someone else. Pick another slot or day.']);
        }
    }
}
