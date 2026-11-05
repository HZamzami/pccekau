<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

class CathsRelationManager extends InterventionsRelationManager
{
    protected static ?string $title = 'Cath';

    protected static ?string $category = 'cath';
}
