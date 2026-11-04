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
            ->columns([
                TextColumn::make('booking_date')
                    ->label('Date')
                    ->date('D d M'),

                TextColumn::make('slot_number')
                    ->label('Slot')
                    ->formatStateUsing(fn ($state) => "#{$state}")
                    ->placeholder('—'),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->placeholder('Unassigned')
                    ->url(fn (ProcedureBooking $record) => $record->patient ? PatientResource::getUrl('view', ['record' => $record->patient_id]) : null),

                TextColumn::make('procedure')
                    ->state(fn (ProcedureBooking $record) => $record->procedure ?: $record->intervention_type?->getLabel())
                    ->placeholder('—'),

                TextColumn::make('staff.name')
                    ->label('Consultant')
                    ->placeholder('—'),

                TextColumn::make('procedure_status')
                    ->label('Status')
                    ->badge(),
            ])
            ->emptyStateHeading('Nothing booked this week')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
