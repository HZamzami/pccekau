<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

class SurgeriesRelationManager extends InterventionsRelationManager
{
    protected static ?string $title = 'Surgery';

    protected static ?string $category = 'surgery';
}
