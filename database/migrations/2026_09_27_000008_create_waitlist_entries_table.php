<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('procedure')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('priority')->default('routine');
            $table->string('mobile')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('waiting');
            // No FK constraint: see the matching comment in
            // create_procedure_bookings_table (circular reference).
            $table->foreignId('booking_id')->nullable();
            $table->text('removed_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
