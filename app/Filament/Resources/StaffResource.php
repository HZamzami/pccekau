<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Models\Staff;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Schedules';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Staff';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Select::make('role')
                        ->options(Staff::$roleLabels)
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    Select::make('specialty')
                        ->options(Staff::$specialtyLabels)
                        ->nullable()
                        ->placeholder('Not specified'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                ]),

                Select::make('user_id')
                    ->label('Login account')
                    ->relationship(
                        'user',
                        'name',
                        modifyQueryUsing: fn ($query) => $query->whereBelongsTo(\Filament\Facades\Filament::getTenant()),
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Optional — link this staff member to a panel login.')
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Staff::$roleLabels[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'consultant'      => 'info',
                        'fellow'          => 'warning',
                        'surgeon'         => 'danger',
                        'specialist'      => 'success',
                        'technician'      => 'primary',
                        'resident'        => 'warning',
                        'medical_student' => 'gray',
                        default           => 'gray',
                    }),

                TextColumn::make('specialty')
                    ->formatStateUsing(fn ($state) => Staff::$specialtyLabels[$state] ?? $state)
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(Staff::$roleLabels),

                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('No staff yet')
            ->emptyStateDescription('Add your consultants and fellows first — they appear in the doctor dropdowns on reports, clinic visits, and on-call schedules.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit'   => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
