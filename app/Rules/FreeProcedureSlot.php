<?php

namespace App\Rules;

use App\Models\ProcedureBooking;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class FreeProcedureSlot implements ValidationRule
{
    public function __construct(
        private ?string $date,
        private ?string $slotType,
        private ?int $ignoreBookingId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->date || ! $this->slotType) {
            return;
        }

        if ((int) $value < 1) {
            $fail('Slot number must be at least 1.');

            return;
        }

        $taken = ProcedureBooking::query()
            ->with('patient')
            ->whereDate('booking_date', Carbon::parse($this->date))
            ->where('slot_type', $this->slotType)
            ->where('slot_number', (int) $value)
            ->when($this->ignoreBookingId, fn ($query, $id) => $query->whereKeyNot($id))
            ->first();

        if ($taken) {
            $who = $taken->patient?->name ?? 'another booking';
            $fail("This slot is already booked for {$who}. Pick another slot or day.");
        }
    }
}
