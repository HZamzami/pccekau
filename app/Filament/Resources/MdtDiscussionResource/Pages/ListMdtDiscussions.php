<?php

namespace App\Filament\Resources\MdtDiscussionResource\Pages;

use App\Filament\Resources\MdtDiscussionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMdtDiscussions extends ListRecords
{
    protected static string $resource = MdtDiscussionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
