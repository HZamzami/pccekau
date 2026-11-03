<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->string('category')->default('other')->change();
        });
    }

    public function down(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->enum('category', [
                'referral', 'consent', 'lab', 'external_report',
                'operative_note', 'discharge_summary', 'surgeon_discussion_note', 'other',
            ])->default('other')->change();
        });
    }
};
