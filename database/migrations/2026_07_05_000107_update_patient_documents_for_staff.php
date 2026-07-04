<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->foreignId('uploaded_by_id')->nullable()->after('files')->constrained('staff')->nullOnDelete();
        });

        DB::statement('
            UPDATE patient_documents
            SET uploaded_by_id = (SELECT id FROM staff WHERE staff.name = patient_documents.uploaded_by LIMIT 1)
            WHERE uploaded_by IS NOT NULL
        ');

        Schema::table('patient_documents', function (Blueprint $table) {
            $table->dropColumn('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->string('uploaded_by')->nullable()->after('files');
            $table->dropForeign(['uploaded_by_id']);
            $table->dropColumn('uploaded_by_id');
        });
    }
};
