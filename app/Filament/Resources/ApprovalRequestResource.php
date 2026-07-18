<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalProcedure;
use App\Enums\ApprovalStatus;
use App\Filament\Resources\ApprovalRequestResource\Pages;
use App\Models\ApprovalRequest;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class ApprovalRequestResource extends Resource
{
    protected static ?string $model = ApprovalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form->schema(static::formSchema());
    }

    /**
     * Shared with the patient-chart relation manager, which drops the
     * patient select (the owner record supplies it).
     */
    public static function formSchema(bool $withPatient = true): array
    {
        return [
            Section::make()->schema([
                Grid::make(2)->schema(array_filter([
                    $withPatient ? Select::make('patient_id')->default(fn () => request()->integer('patient_id') ?: null)
                        ->label('Patient')
                        ->relationship('patient', 'name')
                        ->searchable(['name', 'mrn'])
                        ->preload()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}") : null,

                    DatePicker::make('procedure_date')
                        ->label('Date of procedure')
                        ->helperText('Leave empty if not scheduled yet.')
                        ->nullable(),
                ])),

                Textarea::make('diagnosis')
                    ->rows(2)
                    ->required()
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    Select::make('procedure')
                        ->options(ApprovalProcedure::class)
                        ->required(),

                    Select::make('status')
                        ->options(ApprovalStatus::class)
                        ->default(ApprovalStatus::Pending)
                        ->required()
                        ->visibleOn('edit'),
                ]),
            ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.mrn')
                    ->label('MRN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('patient.nationality')
                    ->label('Nationality')
                    ->placeholder('—'),

                TextColumn::make('procedure_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('patient.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (ApprovalRequest $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('diagnosis')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('procedure')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ApprovalStatus::class),

                SelectFilter::make('procedure')
                    ->options(ApprovalProcedure::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                static::approveAction(),
                static::rejectAction(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No approval requests')
            ->emptyStateDescription('Request approval for a procedure — surgery, cath, EP, or imaging.');
    }

    // Shared by the resource table and the patient-chart relation manager.
    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (ApprovalRequest $record) => $record->status === ApprovalStatus::Pending && (auth()->user()?->canWrite() ?? false))
            ->requiresConfirmation()
            ->action(fn (ApprovalRequest $record) => $record->update(['status' => ApprovalStatus::Approved]));
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (ApprovalRequest $record) => $record->status === ApprovalStatus::Pending && (auth()->user()?->canWrite() ?? false))
            ->requiresConfirmation()
            ->action(fn (ApprovalRequest $record) => $record->update(['status' => ApprovalStatus::Rejected]));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListApprovalRequests::route('/'),
            'create' => Pages\CreateApprovalRequest::route('/create'),
            'edit'   => Pages\EditApprovalRequest::route('/{record}/edit'),
        ];
    }
}
