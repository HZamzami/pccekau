<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

class InvasiveEpRelationManager extends InterventionsRelationManager
{
    protected static ?string $title = 'Invasive EP';

    protected static ?string $category = 'ep';
}
