<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fellows_rotations', function (Blueprint $table) {
            $table->foreignId('fellow_id')->nullable()->after('end_date')->constrained('staff')->nullOnDelete();
            $table->dropColumn('fellow_name');
        });
    }

    public function down(): void
    {
        Schema::table('fellows_rotations', function (Blueprint $table) {
            $table->string('fellow_name')->nullable()->after('end_date');
            $table->dropForeign(['fellow_id']);
            $table->dropColumn('fellow_id');
        });
    }
};
