<?php

namespace App\Filament\Resources\WaitlistEntryResource\Pages;

use App\Filament\Resources\WaitlistEntryResource;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreateWaitlistEntry extends CreateRecord
{
    protected static string $resource = WaitlistEntryResource::class;

    /** Set by the "Add to wait-list" buttons to limit the categories offered. */
    #[Url]
    public ?string $group = null;
}
