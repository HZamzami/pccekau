<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OncallScheduleResource\Pages;
use App\Models\OncallSchedule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

    protected static ?string $navigationGroup = 'Schedules';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'On-Call Schedule';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Week')->schema([
                Grid::make(2)->schema([
                    DatePicker::make('week_start')
                        ->label('Week Start (Sunday)')
                        ->required(),

                    TextInput::make('hijri_date_range')
                        ->label('Hijri Date Range')
                        ->placeholder('e.g. 1/7/1447 – 7/7/1447'),
                ]),
            ]),

            Section::make('Weekly Roles')->schema([
                Grid::make(3)->schema([
                    TextInput::make('clinic_doctor')->label('Clinic'),
                    TextInput::make('inpatient_doctor')->label('Inpatient'),
                    TextInput::make('consultation_doctor')->label('Consultation'),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('cath_doctor')->label('Cath'),
                    TextInput::make('service_doctor')->label('Service'),
                    TextInput::make('ep_doctor')->label('EP'),
                ]),
            ]),

            Section::make('Daily On-Call')->schema([
                Grid::make(4)->schema([
                    TextInput::make('oncall_sunday')->label('Sunday'),
                    TextInput::make('oncall_monday')->label('Monday'),
                    TextInput::make('oncall_tuesday')->label('Tuesday'),
                    TextInput::make('oncall_wednesday')->label('Wednesday'),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('oncall_thursday')->label('Thursday'),
                    TextInput::make('oncall_friday')->label('Friday'),
                    TextInput::make('oncall_saturday')->label('Saturday'),
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

                TextColumn::make('hijri_date_range')
                    ->label('Hijri'),

                TextColumn::make('clinic_doctor')->label('Clinic'),
                TextColumn::make('inpatient_doctor')->label('Inpatient'),
                TextColumn::make('cath_doctor')->label('Cath'),
                TextColumn::make('service_doctor')->label('Service'),
                TextColumn::make('ep_doctor')->label('EP'),
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
            'index'  => Pages\ListOncallSchedules::route('/'),
            'create' => Pages\CreateOncallSchedule::route('/create'),
            'edit'   => Pages\EditOncallSchedule::route('/{record}/edit'),
        ];
    }
}
