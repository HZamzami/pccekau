<?php

namespace App\Filament\Resources\ProcedureBookingResource\Pages;

use App\Filament\Resources\ProcedureBookingResource;
use App\Filament\Resources\WaitlistEntryResource;
use App\Models\WaitlistEntry;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CreateProcedureBooking extends CreateRecord
{
    protected static string $resource = ProcedureBookingResource::class;

    // One form orders anything: "Schedule now" books it on the calendar,
    // "Add to wait-list" files a wait-list entry to be scheduled later.
    protected function handleRecordCreation(array $data): Model
    {
        if (Arr::pull($data, 'mode') === 'waitlist') {
            return WaitlistEntry::create(Arr::only($data, (new WaitlistEntry)->getFillable()));
        }

        try {
            return parent::handleRecordCreation($data);
        } catch (UniqueConstraintViolationException) {
            // Validation catches taken slots; this covers two people booking
            // the same slot at the same moment.
            throw ValidationException::withMessages(['data.slot_number' => 'This slot was just booked by someone else. Pick another slot or day.']);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->record instanceof WaitlistEntry
            ? WaitlistEntryResource::getUrl('index')
            : parent::getRedirectUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->record instanceof WaitlistEntry ? 'Added to the wait-list' : 'Booked';
    }
}
