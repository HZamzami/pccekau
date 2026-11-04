<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Priority moves from Urgent/Routine to a 1 (most urgent) to 5 scale.
    // Existing Urgent entries become 1 and Routine entries the middle, 3.
    public function up(): void
    {
        DB::table('waitlist_entries')->where('priority', 'urgent')->update(['priority' => '1']);
        DB::table('waitlist_entries')->where('priority', '!=', '1')->update(['priority' => '3']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE waitlist_entries ALTER COLUMN priority DROP DEFAULT');
            DB::statement('ALTER TABLE waitlist_entries ALTER COLUMN priority TYPE smallint USING priority::smallint');
            DB::statement('ALTER TABLE waitlist_entries ALTER COLUMN priority SET DEFAULT 3');

            return;
        }

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority')->default(3)->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE waitlist_entries ALTER COLUMN priority DROP DEFAULT');
            DB::statement("ALTER TABLE waitlist_entries ALTER COLUMN priority TYPE varchar(255) USING (CASE WHEN priority <= 2 THEN 'urgent' ELSE 'routine' END)");
            DB::statement("ALTER TABLE waitlist_entries ALTER COLUMN priority SET DEFAULT 'routine'");

            return;
        }

        Schema::table('waitlist_entries', function (Blueprint $table) {
            $table->string('priority')->default('routine')->change();
        });

        DB::table('waitlist_entries')->whereIn('priority', ['1', '2'])->update(['priority' => 'urgent']);
        DB::table('waitlist_entries')->whereNotIn('priority', ['urgent'])->update(['priority' => 'routine']);
    }
};
