<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultantScheduleResource\Pages;
use App\Models\ConsultantSchedule;
use App\Models\Staff;
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
use Filament\Tables\Table;

class ConsultantScheduleResource extends Resource
{
    protected static ?string $model = ConsultantSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Consultant Schedule';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Week')->schema([
                Grid::make(2)->schema([
                    DatePicker::make('week_start')
                        ->label('Week Start (Sunday)')
                        ->required(),

                    TextInput::make('hijri_date')
                        ->label('Hijri Date')
                        ->placeholder('e.g. 1/7/1447'),
                ]),
            ]),

            Section::make('Consultant Assignments')->schema([
                Grid::make(3)->schema([
                    Select::make('service_staff_id')->label('Service')
                        ->options(fn () => Staff::active()->consultants()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('cath_staff_id')->label('Cath')
                        ->options(fn () => Staff::active()->consultants()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->nullable(),
                    Select::make('ep_staff_id')->label('EP')
                        ->options(fn () => Staff::active()->consultants()->orderBy('name')->pluck('name', 'id'))
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

                TextColumn::make('hijri_date')
                    ->label('Hijri'),

                TextColumn::make('serviceStaff.name')->label('Service'),
                TextColumn::make('cathStaff.name')->label('Cath'),
                TextColumn::make('epStaff.name')->label('EP'),
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
            'index' => Pages\ListConsultantSchedules::route('/'),
            'create' => Pages\CreateConsultantSchedule::route('/create'),
            'edit' => Pages\EditConsultantSchedule::route('/{record}/edit'),
        ];
    }
}
