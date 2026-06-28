<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mdt_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('discussion_date');
            $table->string('age_snapshot')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->unsignedTinyInteger('oxygen_saturation')->nullable();
            $table->text('diagnosis');
            $table->text('reason_for_discussion');
            $table->text('history')->nullable();
            $table->text('exam_findings')->nullable();
            $table->text('echo_findings')->nullable();
            $table->text('cath_findings')->nullable();
            $table->string('specialist_fellow')->nullable();
            $table->text('discussion_results')->nullable();
            $table->string('contact_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mdt_discussions');
    }
};
