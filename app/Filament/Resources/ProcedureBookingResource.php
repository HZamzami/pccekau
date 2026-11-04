<?php

namespace App\Filament\Resources;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Filament\Forms\ProcedureOrderFields;
use App\Filament\Resources\ProcedureBookingResource\Pages;
use App\Filament\Tables\Filters\ChipFilter;
use App\Filament\Tables\Filters\DateRangeFilter;
use App\Models\ProcedureBooking;
use App\Models\WaitlistEntry;
use App\Rules\FreeProcedureSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProcedureBookingResource extends Resource
{
    protected static ?string $model = ProcedureBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Booking';

    protected static ?string $navigationLabel = 'Book a Procedure';

    protected static ?int $navigationSort = 1;

    // Two sidebar entries: Book a Procedure opens straight on the booking
    // form, Booking List on the list. Read-only users only get the list.
    public static function getNavigationItems(): array
    {
        $items = [];

        if (static::canCreate()) {
            $items[] = NavigationItem::make('Book a Procedure')
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName().'.create'))
                ->sort(static::getNavigationSort())
                ->url(static::getUrl('create'));
        }

        $items[] = NavigationItem::make('Booking List')
            ->group(static::getNavigationGroup())
            ->icon('heroicon-o-table-cells')
            ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName().'.index', static::getRouteBaseName().'.edit', static::getRouteBaseName().'.view'))
            ->sort(static::getNavigationSort())
            ->url(static::getUrl('index'));

        return $items;
    }

    public static function form(Form $form): Form
    {
        $scheduling = fn (Get $get) => $get('mode') !== 'waitlist';
        $hasSlots = fn (Get $get) => ProcedureOrderFields::category($get)?->slotTypes() !== [];

        return $form->schema([
            Section::make()->schema([
                ToggleButtons::make('mode')
                    ->label('What do you want to do?')
                    ->options(['schedule' => 'Schedule now', 'waitlist' => 'Add to wait-list'])
                    ->icons(['schedule' => 'heroicon-o-calendar-days', 'waitlist' => 'heroicon-o-queue-list'])
                    ->default('schedule')
                    ->inline()
                    ->live()
                    ->visibleOn('create'),

                Grid::make(2)->schema([
                    ProcedureOrderFields::patient()
                        ->required(fn (Get $get) => ! $scheduling($get)),

                    ProcedureOrderFields::consultant(),
                ]),

                Grid::make(3)->schema([
                    ProcedureOrderFields::categorySelect(),
                    ProcedureOrderFields::interventionType()
                        ->required($scheduling),
                    ProcedureOrderFields::procedure(),
                ]),

                Grid::make(4)->schema([
                    DatePicker::make('booking_date')
                        ->label('Date')
                        ->required()
                        ->visible($scheduling),

                    Select::make('slot_type')
                        ->label('Slot')
                        ->options(fn (Get $get) => array_intersect_key(
                            ProcedureBooking::$slotTypeLabels,
                            array_flip(ProcedureOrderFields::category($get)?->slotTypes() ?? array_keys(ProcedureBooking::$slotTypeLabels)),
                        ))
                        ->live()
                        ->visible(fn (Get $get) => $scheduling($get) && $hasSlots($get)),

                    TextInput::make('slot_number')
                        ->label('Slot #')
                        ->helperText('Optional — each number can be used once per day')
                        ->numeric()
                        ->maxValue(255)
                        ->rule(fn (Get $get, ?ProcedureBooking $record) => new FreeProcedureSlot($get('booking_date'), $get('slot_type'), $record?->getKey()))
                        ->visible(fn (Get $get) => $scheduling($get) && $hasSlots($get)),

                    Select::make('procedure_status')
                        ->label('Status')
                        ->options(ProcedureStatus::class)
                        ->default(ProcedureStatus::Requested)
                        ->required()
                        ->visible($scheduling),

                    Select::make('priority')
                        ->options(WaitlistPriority::class)
                        ->default(WaitlistPriority::P3)
                        ->required()
                        ->visible(fn (Get $get) => ! $scheduling($get)),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('mobile')
                        ->tel(),

                    TextInput::make('booked_by')
                        ->label('Booked by')
                        ->visible($scheduling),
                ]),

                ProcedureOrderFields::diagnosis(),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('slot_type')
                    ->label('Slot')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ProcedureBooking::$slotTypeLabels[$state] ?? $state)
                    ->placeholder('No slot'),

                TextColumn::make('slot_number')
                    ->label('Slot #')
                    ->placeholder('—'),

                TextColumn::make('category')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->url(fn (ProcedureBooking $record) => $record->patient_id ? PatientResource::getUrl('view', ['record' => $record->patient_id]) : null),

                TextColumn::make('procedure')
                    ->placeholder('—'),

                TextColumn::make('staff.name')
                    ->label('Consultant')
                    ->placeholder('—'),

                TextColumn::make('procedure_status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                ChipFilter::make('category')
                    ->quick()
                    ->options(ProcedureCategory::options()),

                // Scheduled and Postponed first: those are what the list is mostly worked from.
                ChipFilter::make('procedure_status')
                    ->label('Status')
                    ->options(collect([ProcedureStatus::Scheduled, ProcedureStatus::Postponed])
                        ->merge(ProcedureStatus::cases())
                        ->unique(fn (ProcedureStatus $status) => $status->value)
                        ->mapWithKeys(fn (ProcedureStatus $status) => [$status->value => $status->getLabel()])
                        ->all()),

                ChipFilter::make('slot_type')
                    ->label('Slot')
                    ->options(ProcedureBooking::$slotTypeLabels),

                SelectFilter::make('staff_id')
                    ->label('Consultant')
                    ->multiple()
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload(),

                DateRangeFilter::make('booking_date'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                static::returnToWaitlistAction(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('booking_date', 'desc')
            ->emptyStateHeading('No procedure bookings yet')
            ->emptyStateDescription('Order any procedure here: cath, surgery, imaging, EP tests or case discussions. Schedule it now or add it to the wait-list.');
    }

    public static function returnToWaitlistAction(): Action
    {
        return Action::make('returnToWaitlist')
            ->label('Return to Wait-list')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (ProcedureBooking $record) => $record->procedure_status === ProcedureStatus::Postponed)
            ->requiresConfirmation()
            ->action(function (ProcedureBooking $record) {
                if ($record->waitlist_entry_id) {
                    WaitlistEntry::withTrashed()->find($record->waitlist_entry_id)?->restore();
                    WaitlistEntry::where('id', $record->waitlist_entry_id)->update([
                        'status' => WaitlistStatus::Waiting,
                        'booking_id' => null,
                    ]);
                } else {
                    WaitlistEntry::create([
                        'patient_id' => $record->patient_id,
                        'staff_id' => $record->staff_id,
                        'category' => $record->category,
                        'intervention_type' => $record->intervention_type,
                        'procedure' => $record->procedure,
                        'diagnosis' => $record->diagnosis,
                        'notes' => $record->notes,
                        'status' => WaitlistStatus::Waiting,
                    ]);
                }

                $record->update(['procedure_status' => ProcedureStatus::Requested]);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcedureBookings::route('/'),
            'create' => Pages\CreateProcedureBooking::route('/create'),
            'edit' => Pages\EditProcedureBooking::route('/{record}/edit'),
        ];
    }
}
