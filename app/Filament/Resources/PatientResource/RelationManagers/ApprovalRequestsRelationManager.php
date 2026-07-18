<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\ApprovalProcedure;
use App\Enums\ApprovalStatus;
use App\Filament\Resources\ApprovalRequestResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApprovalRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalRequests';

    protected static ?string $title = 'Approval Requests';

    public function form(Form $form): Form
    {
        return $form->schema(ApprovalRequestResource::formSchema(withPatient: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('diagnosis')
            ->columns([
                TextColumn::make('procedure_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('diagnosis')
                    ->limit(40)
                    ->tooltip(fn (TextColumn $column): ?string => strlen((string) $column->getState()) > 40 ? $column->getState() : null),

                TextColumn::make('procedure')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ApprovalStatus::class),

                SelectFilter::make('procedure')
                    ->options(ApprovalProcedure::class),
            ])
            ->headerActions([
                CreateAction::make()->url(fn () => ApprovalRequestResource::getUrl('create', ['patient_id' => $this->getOwnerRecord()->getKey()]))->openUrlInNewTab()
                    ->label('Request approval'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->url(fn ($record) => ApprovalRequestResource::getUrl('edit', ['record' => $record]))->openUrlInNewTab(),
                ApprovalRequestResource::approveAction(),
                ApprovalRequestResource::rejectAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
