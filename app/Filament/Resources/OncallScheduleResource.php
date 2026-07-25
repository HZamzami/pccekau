<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OncallScheduleResource\Pages;
use App\Models\OncallSchedule;
use App\Models\Staff;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OncallScheduleResource extends Resource
{
    protected static ?string $model = OncallSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Weekly Coverage';

    protected static ?string $modelLabel = 'weekly coverage schedule';

    protected static ?string $pluralModelLabel = 'Specialists/Fellows Weekly Coverage';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Week')->schema([
                DatePicker::make('week_start')
                    ->label('Week Start (Sunday)')
                    ->required(),
            ]),

            Section::make('Weekly Roles')->schema([
                Grid::make(2)->schema([
                    Select::make('clinic_staff_id')->label('Clinic')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('inpatient_staff_id')->label('Inpatient')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('consultation_staff_id')->label('Consultation')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('cath_staff_id')->label('Cath')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                ]),
            ]),

            Section::make('Daily On-Call')->schema([
                Grid::make(4)->schema([
                    Select::make('oncall_sunday_id')->label('Sunday')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('oncall_monday_id')->label('Monday')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('oncall_tuesday_id')->label('Tuesday')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('oncall_wednesday_id')->label('Wednesday')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                ]),

                Grid::make(3)->schema([
                    Select::make('oncall_thursday_id')->label('Thursday')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('oncall_friday_id')->label('Friday')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('oncall_saturday_id')->label('Saturday')
                        ->options(fn () => Staff::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                ]),
            ]),

            Section::make()->schema([
                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('week_start')
                    ->label('Week Start')
                    ->date()
                    ->sortable(),

                TextColumn::make('clinicStaff.name')->label('Clinic'),
                TextColumn::make('inpatientStaff.name')->label('Inpatient'),
                TextColumn::make('consultationStaff.name')->label('Consultation'),
                TextColumn::make('cathStaff.name')->label('Cath'),
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
            ->defaultSort('week_start', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOncallSchedules::route('/'),
            'create' => Pages\CreateOncallSchedule::route('/create'),
            'edit' => Pages\EditOncallSchedule::route('/{record}/edit'),
        ];
    }
}
