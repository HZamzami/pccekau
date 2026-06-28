<?php

namespace App\Filament\Resources\FellowsRotationResource\Pages;

use App\Filament\Resources\FellowsRotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFellowsRotation extends EditRecord
{
    protected static string $resource = FellowsRotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
