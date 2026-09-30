<?php

namespace App\Filament\Pages;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\ProcedureBookingResource;
use App\Models\ProcedureBooking;
use App\Models\Staff;
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

    // Mirrors the original booking sheet: Day Care Cath had 2 concurrent
    // slots, Inpatient Cath and MRI/CT had 1 each. OR and Echo were added later.
    public static array $columns = [
        'cath_day_care_1' => ['slot_type' => 'cath_day_care', 'slot_number' => 1, 'label' => 'Day Care Cath — Case 1'],
        'cath_day_care_2' => ['slot_type' => 'cath_day_care', 'slot_number' => 2, 'label' => 'Day Care Cath — Case 2'],
        'cath_inpatient_1' => ['slot_type' => 'cath_inpatient', 'slot_number' => 1, 'label' => 'Inpatient Cath'],
        'mri_ct_1' => ['slot_type' => 'mri_ct', 'slot_number' => 1, 'label' => 'MRI / CT'],
        'or_1' => ['slot_type' => 'or', 'slot_number' => 1, 'label' => 'Operating Room'],
        'echo_1' => ['slot_type' => 'echo', 'slot_number' => 1, 'label' => 'Echo'],
    ];

    public ?string $category = null;

    public ?string $staffId = null;

    public ?string $status = null;

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

    public function clearFilters(): void
    {
        $this->reset(['category', 'staffId', 'status']);
    }

    /** @return array<string, array{slot_type: string, slot_number: int, label: string}> */
    public function getVisibleColumns(): array
    {
        $category = ProcedureCategory::tryFrom((string) $this->category);

        if (! $category) {
            return static::$columns;
        }

        return array_filter(static::$columns, fn (array $column) => in_array($column['slot_type'], $category->slotTypes(), true));
    }

    /** @return array<string, string> */
    public function getCategoryOptions(): array
    {
        return collect(ProcedureCategory::cases())->mapWithKeys(fn (ProcedureCategory $c) => [$c->value => $c->getLabel()])->all();
    }

    /** @return array<string, string> */
    public function getStatusOptions(): array
    {
        return collect(ProcedureStatus::cases())->mapWithKeys(fn (ProcedureStatus $s) => [$s->value => $s->getLabel()])->all();
    }

    /** @return Collection<int, string> */
    public function getStaffOptions(): Collection
    {
        return Staff::active()->orderBy('name')->pluck('name', 'id');
    }

    /** @return Collection<int, array{date: Carbon, label: string, cells: array<string, ?ProcedureBooking>}> */
    public function getGrid(): Collection
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->addDays(6);

        $bookings = ProcedureBooking::with(['patient', 'staff'])
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->when($this->category, fn ($query, $category) => $query->where('category', $category))
            ->when($this->staffId, fn ($query, $staffId) => $query->where('staff_id', $staffId))
            ->when($this->status, fn ($query, $status) => $query->where('procedure_status', $status))
            ->get();

        $columns = $this->getVisibleColumns();

        return collect(range(0, 6))->map(function (int $i) use ($start, $bookings, $columns) {
            $date = $start->copy()->addDays($i);

            $cells = collect($columns)->mapWithKeys(function (array $column, string $key) use ($date, $bookings) {
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
