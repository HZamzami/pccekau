<?php

namespace App\Filament\Resources\MdtDiscussionResource\Pages;

use App\Filament\Resources\MdtDiscussionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMdtDiscussion extends EditRecord
{
    protected static string $resource = MdtDiscussionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
