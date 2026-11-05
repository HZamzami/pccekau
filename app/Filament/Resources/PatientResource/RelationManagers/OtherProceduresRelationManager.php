<?php

namespace App\Filament\Resources\PatientResource\RelationManagers;

use App\Enums\InterventionType;
use Illuminate\Database\Eloquent\Model;

class OtherProceduresRelationManager extends InterventionsRelationManager
{
    protected static ?string $title = 'Other procedures';

    // Only shown for patients who have one, so the tab row stays short.
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return parent::canViewForRecord($ownerRecord, $pageClass)
            && $ownerRecord->interventions()->where('type', InterventionType::Other)->exists();
    }
}
