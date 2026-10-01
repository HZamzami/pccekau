<?php

namespace App\Filament\Resources\MdtDiscussionResource\Pages;

use App\Filament\Resources\MdtDiscussionResource;
use App\Filament\Resources\WaitlistEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMdtDiscussions extends ListRecords
{
    protected static string $resource = MdtDiscussionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            WaitlistEntryResource::addAction('case_discussion'),
            Actions\CreateAction::make(),
        ];
    }
}
