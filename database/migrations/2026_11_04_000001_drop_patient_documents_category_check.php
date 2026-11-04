<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The earlier string conversion only changed the column type; on pgsql the
    // enum()'s CHECK constraint survived ->change(), so the new
    // 'surgeon_discussion_note' category still failed on insert with a 500.
    // Valid categories are enforced by PatientDocument::$categoryLabels.
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE patient_documents DROP CONSTRAINT IF EXISTS patient_documents_category_check');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE patient_documents ADD CONSTRAINT patient_documents_category_check CHECK (category::text = ANY (ARRAY['referral', 'consent', 'lab', 'external_report', 'operative_note', 'discharge_summary', 'surgeon_discussion_note', 'other']::text[]))");
        }
    }
};
