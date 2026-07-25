<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The patient form's Surgical History and Current Plan boxes are merged
// into a single "Summary & Current Plan" field backed by current_plan.
// Blood type was unused clinically and is dropped.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('patients')
            ->whereNotNull('surgical_history')
            ->where('surgical_history', '!=', '')
            ->orderBy('id')
            ->each(function (object $patient) {
                $merged = trim($patient->surgical_history."\n\n".($patient->current_plan ?? ''));

                DB::table('patients')->where('id', $patient->id)->update(['current_plan' => $merged]);
            });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['surgical_history', 'blood_type']);
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->text('surgical_history')->nullable();
            $table->string('blood_type', 5)->nullable();
        });
    }
};
