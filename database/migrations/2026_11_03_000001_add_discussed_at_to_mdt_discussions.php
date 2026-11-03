<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdt_discussions', function (Blueprint $table) {
            $table->date('discussed_at')->nullable()->after('discussed');
        });
    }

    public function down(): void
    {
        Schema::table('mdt_discussions', function (Blueprint $table) {
            $table->dropColumn('discussed_at');
        });
    }
};
