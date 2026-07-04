<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Models\PatientDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    TextInput::make('label')
                        ->required()
                        ->maxLength(255),

                    Select::make('category')
                        ->options(PatientDocument::$categoryLabels)
                        ->default('other')
                        ->required(),
                ]),

                Select::make('uploaded_by_id')
                    ->label('Uploaded by')
                    ->relationship('uploadedBy', 'name', fn ($query) => $query->active())
                    ->searchable()
                    ->preload(),

                FileUpload::make('files')
                    ->multiple()
                    ->required()
                    ->disk('local')
                    ->visibility('private')
                    ->directory('patient-documents')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(10240)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('label')->searchable(),

                TextColumn::make('category')
                    ->formatStateUsing(fn ($state) => PatientDocument::$categoryLabels[$state] ?? $state)
                    ->badge()
                    ->color('gray'),

                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded by'),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(PatientDocument::$categoryLabels),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form(fn (PatientDocument $record) => [
                        Select::make('index')
                            ->label('File')
                            ->options(collect($record->files)->map(fn ($path) => basename($path)))
                            ->default(0)
                            ->required(),
                    ])
                    ->action(fn (PatientDocument $record, array $data) => redirect()->route('patient-documents.download', [
                        'document' => $record,
                        'index' => $data['index'],
                    ])),
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
