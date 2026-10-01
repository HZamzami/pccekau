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

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Procedure Calendar';

    protected static string $view = 'filament.pages.procedure-calendar-page';

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

    /** @return array<string, array{slot_type: ?string, slot_number: ?int, label: string}> */
    public static function columns(): array
    {
        $columns = [];

        foreach (ProcedureBooking::$slotCapacity as $type => $capacity) {
            foreach (range(1, $capacity) as $number) {
                $label = ProcedureBooking::$slotTypeLabels[$type];
                $columns["{$type}_{$number}"] = [
                    'slot_type' => $type,
                    'slot_number' => $number,
                    'label' => $capacity > 1 ? "{$label} — Case {$number}" : $label,
                ];
            }
        }

        // Anything booked without a slot: EP tests, case discussions, or a
        // booking whose slot # was left empty.
        $columns['no_slot'] = ['slot_type' => null, 'slot_number' => null, 'label' => 'No slot'];

        return $columns;
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
            return static::columns();
        }

        return array_filter(static::columns(), fn (array $column) => $column['slot_type'] === null
            || in_array($column['slot_type'], $category->slotTypes(), true));
    }

    /** @return array<string, string> */
    public function getCategoryOptions(): array
    {
        return ProcedureCategory::options();
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

    /** @return Collection<int, array{date: Carbon, label: string, cells: Collection<string, Collection<int, ProcedureBooking>>}> */
    public function getGrid(): Collection
    {
        $start = Carbon::parse($this->weekStart);
        $end = $start->copy()->addDays(6);

        $bookings = ProcedureBooking::with(['patient', 'staff'])
            ->whereDate('booking_date', '>=', $start)
            ->whereDate('booking_date', '<=', $end)
            ->when($this->category, fn ($query, $category) => $query->where('category', $category))
            ->when($this->staffId, fn ($query, $staffId) => $query->where('staff_id', $staffId))
            ->when($this->status, fn ($query, $status) => $query->where('procedure_status', $status))
            ->get();

        $columns = $this->getVisibleColumns();

        return collect(range(0, 6))->map(function (int $i) use ($start, $bookings, $columns) {
            $date = $start->copy()->addDays($i);

            $sameDay = $bookings->filter(fn (ProcedureBooking $b) => $b->booking_date->isSameDay($date));

            $cells = collect($columns)->mapWithKeys(fn (array $column, string $key) => [
                $key => $key === 'no_slot'
                    ? $sameDay->filter(fn (ProcedureBooking $b) => ! $b->slot_type || ! $b->slot_number)->values()
                    : $sameDay->filter(fn (ProcedureBooking $b) => $b->slot_type === $column['slot_type'] && $b->slot_number === $column['slot_number'])->values(),
            ]);

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
