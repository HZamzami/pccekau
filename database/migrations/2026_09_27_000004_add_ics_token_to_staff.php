<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('ics_token', 40)->nullable()->unique()->after('is_active');
        });

        DB::table('staff')->whereNull('ics_token')->orderBy('id')->pluck('id')->each(
            fn ($id) => DB::table('staff')->where('id', $id)->update(['ics_token' => Str::random(40)])
        );
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('ics_token');
        });
    }
};
