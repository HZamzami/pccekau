<?php

namespace App\Filament\Resources;

use App\Enums\CoverageRole;
use App\Filament\Forms\DailyAssignmentsForm;
use App\Filament\Resources\ConsultantScheduleResource\Pages;
use App\Models\ConsultantSchedule;
use App\Models\Staff;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
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

    protected static ?string $navigationGroup = 'Schedules';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Consultant Schedule';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Week')->schema([
                DatePicker::make('week_start')
                    ->label('Week Start (Sunday)')
                    ->required(),
            ]),

            Section::make('Daily Consultant Assignments')
                ->description('Pick a doctor for each day, or use "Whole week" to fill all 7 days at once.')
                ->schema(DailyAssignmentsForm::schema(CoverageRole::forConsultants(), fn () => Staff::active()->consultants()->orderBy('name')->pluck('name', 'id')->all())),

            Section::make()->schema([
                Textarea::make('notes')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('assignments.staff'))
            ->columns([
                TextColumn::make('week_start')
                    ->label('Week Start')
                    ->date()
                    ->sortable(),

                TextColumn::make('doctors')
                    ->label('Doctors this week')
                    ->state(fn ($record) => $record->assignments->pluck('staff.name')->filter()->unique()->sort()->values()->all())
                    ->badge()
                    ->placeholder('—'),
            ])
            ->actions([
                ViewAction::make()
                    ->mutateRecordDataUsing(fn (array $data, $record) => [...$data, 'assignments' => $record->assignmentFormState()]),
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
