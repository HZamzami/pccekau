<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // The clinic admin vouches for accounts they create — no verification
    // email round-trip needed (unlike public self-registration).
    protected function afterCreate(): void
    {
        $this->record->forceFill(['email_verified_at' => now()])->save();
    }
}
