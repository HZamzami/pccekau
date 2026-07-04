<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imaging_report_mdt_discussion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mdt_discussion_id')->constrained()->cascadeOnDelete();

            $table->unique(['imaging_report_id', 'mdt_discussion_id'], 'report_mdt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_report_mdt_discussion');
    }
};
