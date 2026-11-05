<?php

namespace App\Filament\Widgets;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\ProcedureBookingResource;
use App\Models\ProcedureBooking;
use Carbon\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

// This week's live bookings of one category, as a standard table; the
// dashboard shows one for surgeries and one for caths side by side.
abstract class WeeklyBookingsWidget extends TableWidget
{
    // Same table, but the card fills the row so the two sit level.
    protected static string $view = 'filament.widgets.weekly-bookings-widget';

    protected int|string|array $columnSpan = 1;

    abstract protected function category(): ProcedureCategory;

    public function table(Table $table): Table
    {
        $start = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        return $table
            ->heading($this->category() === ProcedureCategory::Surgery ? 'Surgeries this week' : 'Caths this week')
            ->query(
                ProcedureBooking::query()
                    ->with(['patient', 'staff'])
                    ->where('category', $this->category())
                    ->whereIn('procedure_status', [ProcedureStatus::Requested, ProcedureStatus::Scheduled, ProcedureStatus::Postponed])
                    ->whereDate('booking_date', '>=', $start)
                    ->whereDate('booking_date', '<=', $start->copy()->addDays(6))
                    ->orderBy('booking_date')
                    ->orderBy('slot_number')
            )
            ->recordUrl(fn (ProcedureBooking $record) => ProcedureBookingResource::getUrl('edit', ['record' => $record]))
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->link()
                    ->url(ProcedureBookingResource::getUrl('index', ['tableFilters' => ['category' => ['values' => [$this->category()->value]]]])),
            ])
            ->paginated(false)
            // Three columns so it fits half the dashboard; the details sit underneath.
            ->columns([
                TextColumn::make('booking_date')
                    ->label('Date')
                    ->date('D d M')
                    ->description(fn (ProcedureBooking $record) => $record->slot_number ? "Slot #{$record->slot_number}" : null),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->placeholder('Unassigned')
                    ->description(fn (ProcedureBooking $record) => collect([
                        $record->procedure ?: $record->exactProcedure()?->getLabel(),
                        $record->staff?->name,
                    ])->filter()->implode(' · ') ?: null)
                    ->url(fn (ProcedureBooking $record) => $record->patient ? PatientResource::getUrl('view', ['record' => $record->patient_id]) : null),

                TextColumn::make('procedure_status')
                    ->label('Status')
                    ->badge()
                    ->alignEnd(),
            ])
            ->emptyStateHeading('Nothing booked this week')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
