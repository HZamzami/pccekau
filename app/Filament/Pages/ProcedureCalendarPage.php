<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProcedureBookingResource;
use App\Models\ProcedureBooking;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ProcedureCalendarPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Procedure Calendar';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Procedure Calendar';

    protected static string $view = 'filament.pages.procedure-calendar-page';

    // Mirrors the original booking sheet exactly: Day Care Cath had 2
    // concurrent slots, Inpatient Cath and MRI/CT had 1 each.
    public static array $columns = [
        'cath_day_care_1' => ['slot_type' => 'cath_day_care', 'slot_number' => 1, 'label' => 'Day Care Cath — Case 1'],
        'cath_day_care_2' => ['slot_type' => 'cath_day_care', 'slot_number' => 2, 'label' => 'Day Care Cath — Case 2'],
        'cath_inpatient_1' => ['slot_type' => 'cath_inpatient', 'slot_number' => 1, 'label' => 'Inpatient Cath'],
        'mri_ct_1' => ['slot_type' => 'mri_ct', 'slot_number' => 1, 'label' => 'MRI / CT'],
    ];

    public ?string $weekStart = null;

    public function mount(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
    }

    public function currentWeek(): void
    {
        $this->weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    /** @return Collection<int, array{date: Carbon, label: string, cells: array<string, ?ProcedureBooking>}> */
    public function getGrid(): Collection
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->addDays(6);

        $bookings = ProcedureBooking::with(['patient', 'staff'])
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return collect(range(0, 6))->map(function (int $i) use ($start, $bookings) {
            $date = $start->copy()->addDays($i);

            $cells = collect(static::$columns)->mapWithKeys(function (array $column, string $key) use ($date, $bookings) {
                $booking = $bookings->first(fn (ProcedureBooking $b) => $b->booking_date->isSameDay($date)
                    && $b->slot_type === $column['slot_type']
                    && $b->slot_number === $column['slot_number']);

                return [$key => $booking];
            });

            return [
                'date' => $date,
                'label' => $date->format('l'),
                'cells' => $cells,
            ];
        });
    }

    public function getBookingUrl(ProcedureBooking $booking): string
    {
        return ProcedureBookingResource::getUrl('edit', ['record' => $booking]);
    }
}
