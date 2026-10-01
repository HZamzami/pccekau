<?php

namespace App\Filament\Resources;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Filament\Forms\ProcedureOrderFields;
use App\Filament\Resources\WaitlistEntryResource\Pages;
use App\Models\ProcedureBooking;
use App\Models\WaitlistEntry;
use App\Rules\FreeProcedureSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class WaitlistEntryResource extends Resource
{
    protected static ?string $model = WaitlistEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?string $navigationLabel = 'Wait-list';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    ProcedureOrderFields::patient()->required(),
                    ProcedureOrderFields::consultant(),
                ]),

                Grid::make(3)->schema([
                    ProcedureOrderFields::categorySelect(),
                    ProcedureOrderFields::interventionType()->required(false),
                    ProcedureOrderFields::procedure(),
                ]),

                Grid::make(2)->schema([
                    Select::make('priority')
                        ->options(WaitlistPriority::class)
                        ->default(WaitlistPriority::Routine)
                        ->required(),

                    TextInput::make('mobile')
                        ->tel(),
                ]),

                ProcedureOrderFields::diagnosis(),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    /** Puts a waiting entry on the calendar; the booking then creates its record on the patient's chart. */
    public static function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label('Schedule')
            ->icon('heroicon-o-calendar-days')
            ->color('success')
            ->visible(fn (WaitlistEntry $record) => $record->status === WaitlistStatus::Waiting && (auth()->user()?->canManageSchedule() ?? false))
            ->form([
                Select::make('intervention_type')
                    ->label('Exact procedure')
                    ->options(fn (WaitlistEntry $record) => $record->category?->interventionTypeOptions() ?? [])
                    ->default(fn (WaitlistEntry $record) => $record->intervention_type?->value)
                    ->visible(fn (WaitlistEntry $record) => $record->category?->needsInterventionType() ?? false)
                    ->required(),

                DatePicker::make('booking_date')
                    ->label('Date')
                    ->required(),

                Select::make('slot_type')
                    ->label('Slot')
                    ->options(fn (WaitlistEntry $record) => array_intersect_key(
                        ProcedureBooking::$slotTypeLabels,
                        array_flip($record->category?->slotTypes() ?? array_keys(ProcedureBooking::$slotTypeLabels)),
                    ))
                    ->default(fn (WaitlistEntry $record) => $record->category?->defaultSlotType())
                    ->visible(fn (WaitlistEntry $record) => $record->category?->slotTypes() !== []),

                TextInput::make('slot_number')
                    ->label('Slot #')
                    ->helperText('Optional')
                    ->numeric()
                    ->rule(fn (Get $get) => new FreeProcedureSlot($get('booking_date'), $get('slot_type')))
                    ->visible(fn (WaitlistEntry $record) => $record->category?->slotTypes() !== []),
            ])
            ->action(function (WaitlistEntry $record, array $data, Action $action) {
                try {
                    DB::transaction(function () use ($record, $data) {
                        $booking = ProcedureBooking::create([
                            'patient_id' => $record->patient_id,
                            'booking_date' => $data['booking_date'],
                            'slot_type' => $data['slot_type'] ?? null,
                            'slot_number' => $data['slot_number'] ?? null,
                            'category' => $record->category,
                            'intervention_type' => $data['intervention_type'] ?? $record->intervention_type,
                            'staff_id' => $record->staff_id,
                            'procedure' => $record->procedure,
                            'diagnosis' => $record->diagnosis,
                            'mobile' => $record->mobile,
                            'procedure_status' => ProcedureStatus::Confirmed,
                            'waitlist_entry_id' => $record->id,
                            'notes' => $record->notes,
                        ]);

                        $record->update(['status' => WaitlistStatus::Scheduled, 'booking_id' => $booking->id]);
                    });
                } catch (UniqueConstraintViolationException) {
                    Notification::make()->danger()->title('That slot was just booked by someone else')->body('Pick another slot or day.')->send();
                    $action->halt();
                }
            });
    }

    public static function removeAction(): Action
    {
        return Action::make('remove')
            ->label('Remove')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (WaitlistEntry $record) => $record->status === WaitlistStatus::Waiting && (auth()->user()?->canManageSchedule() ?? false))
            ->requiresConfirmation()
            ->form([
                Textarea::make('removed_reason')
                    ->label('Reason')
                    ->required()
                    ->rows(2),
            ])
            ->action(fn (WaitlistEntry $record, array $data) => $record->update([
                'status' => WaitlistStatus::Removed,
                'removed_reason' => $data['removed_reason'],
            ]));
    }

    /** "Add to wait-list" button for the list pages, pre-set to that page's kind of procedure. */
    public static function addAction(?string $group = null): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('addToWaitlist')
            ->label('Add to wait-list')
            ->icon('heroicon-o-queue-list')
            ->color('gray')
            ->url(static::getUrl('create', array_filter(['group' => $group])))
            ->visible(fn () => static::canCreate());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.mrn')
                    ->label('MRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable()
                    ->url(fn (WaitlistEntry $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('category')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('procedure')
                    ->placeholder('—'),

                TextColumn::make('staff.name')
                    ->label('Consultant')
                    ->sortable()
                    ->placeholder('Unassigned'),

                TextColumn::make('priority')
                    ->badge(),

                TextColumn::make('days_waiting')
                    ->label('Days Waiting')
                    ->state(fn (WaitlistEntry $record) => $record->days_waiting)
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('created_at', $direction === 'asc' ? 'desc' : 'asc')),

                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('staff_id')
                    ->label('Consultant')
                    ->relationship('staff', 'name'),

                SelectFilter::make('category')
                    ->options(ProcedureCategory::options()),

                SelectFilter::make('priority')
                    ->options(WaitlistPriority::class),

                SelectFilter::make('status')
                    ->options(WaitlistStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                static::scheduleAction(),
                static::removeAction(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at')
            ->emptyStateHeading('No one on the wait-list')
            ->emptyStateDescription('Patients waiting for a procedure that isn\'t scheduled yet go here.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWaitlistEntries::route('/'),
            'create' => Pages\CreateWaitlistEntry::route('/create'),
            'edit' => Pages\EditWaitlistEntry::route('/{record}/edit'),
        ];
    }
}
