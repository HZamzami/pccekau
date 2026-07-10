<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientDocumentResource\Pages;
use App\Models\Patient;
use App\Filament\Resources\PatientResource;
use App\Models\PatientDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

class PatientDocumentResource extends Resource
{
    protected static ?string $model = PatientDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Clinical';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(2)->schema([
                    Select::make('patient_id')
                        ->label('Patient')
                        ->relationship('patient', 'name')
                        ->searchable(['name', 'mrn'])
                        ->preload()
                        ->required()
                        ->getOptionLabelFromRecordUsing(fn (Patient $record) => "{$record->mrn} — {$record->name}"),

                    TextInput::make('label')
                        ->required()
                        ->maxLength(255),
                ]),

                Grid::make(2)->schema([
                    Select::make('category')
                        ->options(PatientDocument::$categoryLabels)
                        ->default('other')
                        ->required(),

                    Select::make('uploaded_by_id')
                        ->label('Uploaded by')
                        ->relationship('uploadedBy', 'name', fn ($query) => $query->active())
                        ->searchable()
                        ->preload(),
                ]),

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
                    ->url(fn (PatientDocument $record) => PatientResource::getUrl('view', ['record' => $record->patient_id])),

                TextColumn::make('label')
                    ->searchable(),

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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPatientDocuments::route('/'),
            'create' => Pages\CreatePatientDocument::route('/create'),
            'edit'   => Pages\EditPatientDocument::route('/{record}/edit'),
        ];
    }
}
