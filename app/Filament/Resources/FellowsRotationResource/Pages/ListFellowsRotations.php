<?php

namespace App\Filament\Resources\FellowsRotationResource\Pages;

use App\Filament\Resources\FellowsRotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFellowsRotations extends ListRecords
{
    protected static string $resource = FellowsRotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
