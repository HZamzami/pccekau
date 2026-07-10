<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Device closures are catheter-based procedures, so the DeviceClosure
    // enum case was folded into CathIntervention. Remap stored rows so the
    // InterventionType cast doesn't fail on the removed value.
    public function up(): void
    {
        DB::table('interventions')
            ->where('type', 'device_closure')
            ->update(['type' => 'cath_intervention']);
    }

    public function down(): void
    {
        // Irreversible: former device closures are indistinguishable from
        // other cath interventions once merged.
    }
};
