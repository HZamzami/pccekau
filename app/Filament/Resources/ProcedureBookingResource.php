<?php

namespace App\Filament\Resources;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Filament\Resources\ProcedureBookingResource\Pages;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    Select::make('patient_id')->default(fn () => request()->integer('patient_id') ?: null)
                        ->label('Patient')
                        ->relationship('patient', 'name')
                        ->searchable(['name', 'mrn'])
                        ->preload()
                        ->nullable()
                        ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                    DatePicker::make('booking_date')
                        ->label('Booking date')
                        ->required(),
                ]),

                Grid::make(3)->schema([
                    Select::make('slot_type')
                        ->options(ProcedureBooking::$slotTypeLabels)
                        ->required()
                        ->live(),

                    TextInput::make('slot_number')
                        ->label('Slot #')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),

                    Select::make('staff_id')
                        ->label('Interventionist')
                        ->relationship('staff', 'name', fn ($query) => $query->active())
                        ->searchable()
                        ->preload(),
                ]),

                Grid::make(3)->schema([
                    Select::make('category')
                        ->options(ProcedureCategory::class)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($category = ProcedureCategory::tryFrom((string) $state)) {
                                $set('slot_type', $category->defaultSlotType());
                            }
                        }),

                    TextInput::make('procedure')
                        ->maxLength(255),

                    Select::make('procedure_status')
                        ->label('Procedure Status')
                        ->options(ProcedureStatus::class)
                        ->default(ProcedureStatus::Ordered)
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('mobile')
                        ->tel(),

                    TextInput::make('booked_by')
                        ->label('Booked by'),
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
                TextColumn::make('booking_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('slot_type')
                    ->label('Slot Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ProcedureBooking::$slotTypeLabels[$state] ?? $state),

                TextColumn::make('slot_number')
                    ->label('Slot #'),

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
                    ->label('Interventionist')
                    ->placeholder('—'),

                TextColumn::make('procedure_status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('slot_type')
                    ->options(ProcedureBooking::$slotTypeLabels),

                SelectFilter::make('category')
                    ->options(ProcedureCategory::class),

                SelectFilter::make('procedure_status')
                    ->label('Status')
                    ->options(ProcedureStatus::class),

                SelectFilter::make('staff_id')
                    ->label('Interventionist')
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
            ->emptyStateDescription('Book cath lab, MRI/CT, OR, or echo slots here instead of a spreadsheet.');
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
