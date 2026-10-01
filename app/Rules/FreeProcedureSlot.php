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

        $capacity = ProcedureBooking::$slotCapacity[$this->slotType] ?? 1;

        if ((int) $value < 1 || (int) $value > $capacity) {
            $fail($capacity === 1
                ? 'This slot type has only one slot per day.'
                : "This slot type has slots 1 to {$capacity}.");

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
