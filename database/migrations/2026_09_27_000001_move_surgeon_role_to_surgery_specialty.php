<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // "Surgeon" moves from being a role to a specialty. Existing surgeon-role
    // staff become consultants with a Surgery specialty.
    public function up(): void
    {
        DB::table('staff')
            ->where('role', 'surgeon')
            ->update(['role' => 'consultant', 'specialty' => 'surgery']);
    }

    public function down(): void
    {
        // Not reversible — we can no longer tell which consultants were
        // originally surgeons.
    }
};
