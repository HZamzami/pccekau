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

    protected static ?string $navigationGroup = 'Booking';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Procedure Calendar';

    protected static string $view = 'filament.pages.procedure-calendar-page';

    /** @var array<string> */
    public array $categories = [];

    public ?string $staffId = null;

    /** @var array<string> */
    public array $statuses = [];

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

    /** @return array<string, array{slot_type: ?string, label: string}> */
    public static function columns(): array
    {
        // One column per slot type; slot numbers are free, so each cell lists
        // that day's bookings in slot-number order.
        $columns = [];

        foreach (ProcedureBooking::$slotTypeLabels as $type => $label) {
            $columns[$type] = ['slot_type' => $type, 'label' => $label];
        }

        // Anything booked without a slot: EP tests, case discussions, or a
        // booking whose slot # was left empty.
        $columns['no_slot'] = ['slot_type' => null, 'label' => 'No slot'];

        return $columns;
    }

    public function clearFilters(): void
    {
        $this->reset(['categories', 'staffId', 'statuses']);
    }

    public function removeFilterValue(string $filter, string $value = ''): void
    {
        match ($filter) {
            'categories' => $this->categories = array_values(array_diff($this->categories, [$value])),
            'statuses' => $this->statuses = array_values(array_diff($this->statuses, [$value])),
            'staffId' => $this->staffId = null,
        };
    }

    /** @return array<int, array{label: string, filter: string, value: string}> the removable tags shown while the Filters panel is closed */
    public function getFilterTags(): array
    {
        $categories = $this->getCategoryOptions();
        $statuses = $this->getStatusOptions();

        return [
            ...array_map(fn ($value) => ['label' => 'Category: '.($categories[$value] ?? $value), 'filter' => 'categories', 'value' => (string) $value], $this->categories),
            ...array_map(fn ($value) => ['label' => 'Status: '.($statuses[$value] ?? $value), 'filter' => 'statuses', 'value' => (string) $value], $this->statuses),
            ...($this->staffId ? [['label' => 'Consultant: '.($this->getStaffOptions()[$this->staffId] ?? ''), 'filter' => 'staffId', 'value' => '']] : []),
        ];
    }

    /** @return array<string, array{slot_type: ?string, label: string}> */
    public function getVisibleColumns(): array
    {
        $categories = array_filter(array_map(fn ($value) => ProcedureCategory::tryFrom((string) $value), $this->categories));

        if ($categories === []) {
            return static::columns();
        }

        $slotTypes = collect($categories)->flatMap(fn (ProcedureCategory $category) => $category->slotTypes())->all();

        return array_filter(static::columns(), fn (array $column) => $column['slot_type'] === null
            || in_array($column['slot_type'], $slotTypes, true));
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
            ->when($this->categories, fn ($query, $categories) => $query->whereIn('category', $categories))
            ->when($this->staffId, fn ($query, $staffId) => $query->where('staff_id', $staffId))
            ->when($this->statuses, fn ($query, $statuses) => $query->whereIn('procedure_status', $statuses))
            ->orderBy('slot_number')
            ->get();

        $columns = $this->getVisibleColumns();

        return collect(range(0, 6))->map(function (int $i) use ($start, $bookings, $columns) {
            $date = $start->copy()->addDays($i);

            $sameDay = $bookings->filter(fn (ProcedureBooking $b) => $b->booking_date->isSameDay($date));

            $cells = collect($columns)->mapWithKeys(fn (array $column, string $key) => [
                $key => $key === 'no_slot'
                    ? $sameDay->filter(fn (ProcedureBooking $b) => ! $b->slot_type || ! $b->slot_number)->values()
                    : $sameDay->filter(fn (ProcedureBooking $b) => $b->slot_type === $column['slot_type'] && $b->slot_number)->values(),
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
