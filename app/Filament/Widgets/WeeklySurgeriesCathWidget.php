<?php

namespace App\Filament\Widgets;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\ProcedureBookingResource;
use App\Models\ProcedureBooking;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class WeeklySurgeriesCathWidget extends Widget
{
    protected static string $view = 'filament.widgets.weekly-surgeries-cath-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getWeekStart(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::SUNDAY);
    }

    /** @return Collection<int, ProcedureBooking> this week's live bookings of one category, in calendar order */
    public function getBookings(ProcedureCategory $category): Collection
    {
        $start = $this->getWeekStart();

        return ProcedureBooking::with(['patient', 'staff'])
            ->where('category', $category)
            ->whereIn('procedure_status', [ProcedureStatus::Requested, ProcedureStatus::Scheduled, ProcedureStatus::Postponed])
            ->whereDate('booking_date', '>=', $start)
            ->whereDate('booking_date', '<=', $start->copy()->addDays(6))
            ->orderBy('booking_date')
            ->orderBy('slot_number')
            ->get();
    }

    /** @return array<int, array{title: string, bookings: Collection<int, ProcedureBooking>, url: string}> */
    public function getSections(): array
    {
        return collect([ProcedureCategory::Surgery, ProcedureCategory::Cath])
            ->map(fn (ProcedureCategory $category) => [
                'title' => $category === ProcedureCategory::Surgery ? 'Surgeries this week' : 'Caths this week',
                'bookings' => $this->getBookings($category),
                'url' => ProcedureBookingResource::getUrl('index', ['activeTab' => $category->value]),
            ])
            ->all();
    }

    public function getBookingUrl(ProcedureBooking $booking): string
    {
        return ProcedureBookingResource::getUrl('edit', ['record' => $booking]);
    }

    public function getPatientUrl(ProcedureBooking $booking): ?string
    {
        return $booking->patient ? PatientResource::getUrl('view', ['record' => $booking->patient_id]) : null;
    }
}
