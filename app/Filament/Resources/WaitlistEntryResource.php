<?php

namespace App\Filament\Resources;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Filament\Resources\WaitlistEntryResource\Pages;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use App\Models\WaitlistEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WaitlistEntryResource extends Resource
{
    protected static ?string $model = WaitlistEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?string $navigationLabel = 'Wait-list';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Select::make('patient_id')->default(fn () => request()->integer('patient_id') ?: null)
                    ->label('Patient')
                    ->relationship('patient', 'name')
                    ->searchable(['name', 'mrn'])
                    ->preload()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                Grid::make(3)->schema([
                    Select::make('category')
                        ->options(ProcedureCategory::class)
                        ->required(),

                    TextInput::make('procedure')
                        ->maxLength(255),

                    Select::make('staff_id')
                        ->label('Interventionist')
                        ->relationship('staff', 'name', fn ($query) => $query->active())
                        ->searchable()
                        ->preload(),
                ]),

                Grid::make(2)->schema([
                    Select::make('priority')
                        ->options(WaitlistPriority::class)
                        ->default(WaitlistPriority::Routine)
                        ->required(),

                    TextInput::make('mobile')
                        ->tel(),
                ]),

                Textarea::make('diagnosis')
                    ->rows(2)
                    ->columnSpanFull(),

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
                    ->label('Interventionist')
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
                    ->label('Interventionist')
                    ->relationship('staff', 'name'),

                SelectFilter::make('category')
                    ->options(ProcedureCategory::class),

                SelectFilter::make('priority')
                    ->options(WaitlistPriority::class),

                SelectFilter::make('status')
                    ->options(WaitlistStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('schedule')
                    ->label('Schedule')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->visible(fn (WaitlistEntry $record) => $record->status === WaitlistStatus::Waiting && (auth()->user()?->canManageSchedule() ?? false))
                    ->form([
                        DatePicker::make('booking_date')
                            ->required(),

                        Select::make('slot_type')
                            ->options(ProcedureBooking::$slotTypeLabels)
                            ->required()
                            ->default(fn (WaitlistEntry $record) => $record->category?->defaultSlotType() ?? 'cath_day_care'),

                        TextInput::make('slot_number')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                    ])
                    ->action(function (WaitlistEntry $record, array $data) {
                        $booking = ProcedureBooking::create([
                            'patient_id' => $record->patient_id,
                            'booking_date' => $data['booking_date'],
                            'slot_type' => $data['slot_type'],
                            'slot_number' => $data['slot_number'],
                            'category' => $record->category,
                            'staff_id' => $record->staff_id,
                            'procedure' => $record->procedure,
                            'diagnosis' => $record->diagnosis,
                            'mobile' => $record->mobile,
                            'procedure_status' => ProcedureStatus::Confirmed,
                            'waitlist_entry_id' => $record->id,
                            'notes' => $record->notes,
                        ]);

                        $record->update(['status' => WaitlistStatus::Scheduled, 'booking_id' => $booking->id]);
                    }),

                Action::make('remove')
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
                    ])),
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
