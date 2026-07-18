<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mdt_discussions', function (Blueprint $table) {
            $table->boolean('discussed')->default(false);
        });

        // Existing discussions that already have recorded results clearly
        // took place — mark them discussed so the new filter starts accurate.
        DB::table('mdt_discussions')
            ->whereNotNull('discussion_results')
            ->where('discussion_results', '!=', '')
            ->update(['discussed' => true]);
    }

    public function down(): void
    {
        Schema::table('mdt_discussions', function (Blueprint $table) {
            $table->dropColumn('discussed');
        });
    }
};
