<?php

namespace App\Filament\Resources\EpStudyResource\Pages;

use App\Filament\Resources\EpStudyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEpStudy extends EditRecord
{
    protected static string $resource = EpStudyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
