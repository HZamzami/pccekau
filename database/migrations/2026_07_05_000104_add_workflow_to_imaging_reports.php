<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->string('status')->default('draft')->index()->after('type');
            $table->foreignId('signed_by')->nullable()->after('notes')->constrained('staff')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable()->after('signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('imaging_reports', function (Blueprint $table) {
            $table->dropForeign(['signed_by']);
            $table->dropColumn(['status', 'signed_by', 'finalized_at']);
        });
    }
};
