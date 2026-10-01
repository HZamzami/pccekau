<?php

namespace App\Filament\Resources;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\WaitlistPriority;
use App\Filament\Forms\ProcedureOrderFields;
use App\Filament\Resources\ProcedureBookingResource\Pages;
use App\Models\ProcedureBooking;
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
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ProcedureBookingResource extends Resource
{
    protected static ?string $model = ProcedureBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?string $navigationLabel = 'Procedure Bookings';

    protected static ?int $navigationSort = 10;

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
                        ->helperText('Optional')
                        ->numeric()
                        ->rule(fn (Get $get, ?ProcedureBooking $record) => new FreeProcedureSlot($get('booking_date'), $get('slot_type'), $record?->getKey()))
                        ->visible(fn (Get $get) => $scheduling($get) && $hasSlots($get)),

                    Select::make('procedure_status')
                        ->label('Status')
                        ->options(ProcedureStatus::class)
                        ->default(ProcedureStatus::Ordered)
                        ->required()
                        ->visible($scheduling),

                    Select::make('priority')
                        ->options(WaitlistPriority::class)
                        ->default(WaitlistPriority::Routine)
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
                SelectFilter::make('slot_type')
                    ->options(ProcedureBooking::$slotTypeLabels),

                SelectFilter::make('category')
                    ->options(ProcedureCategory::options()),

                SelectFilter::make('procedure_status')
                    ->label('Status')
                    ->options(ProcedureStatus::class),

                SelectFilter::make('staff_id')
                    ->label('Consultant')
                    ->relationship('staff', 'name'),

                Filter::make('booking_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('booking_date', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('booking_date', '<=', $date)))
                    ->indicateUsing(fn (array $data) => array_filter([
                        $data['from'] ? 'From '.Carbon::parse($data['from'])->format('d M Y') : null,
                        $data['until'] ? 'Until '.Carbon::parse($data['until'])->format('d M Y') : null,
                    ])),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
