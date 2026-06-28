<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdt_discussions', function (Blueprint $table) {
            $table->foreignId('specialist_fellow_id')->nullable()->after('oxygen_saturation')->constrained('staff')->nullOnDelete();
            $table->dropColumn('specialist_fellow');
        });
    }

    public function down(): void
    {
        Schema::table('mdt_discussions', function (Blueprint $table) {
            $table->string('specialist_fellow')->nullable()->after('oxygen_saturation');
            $table->dropForeign(['specialist_fellow_id']);
            $table->dropColumn('specialist_fellow_id');
        });
    }
};
