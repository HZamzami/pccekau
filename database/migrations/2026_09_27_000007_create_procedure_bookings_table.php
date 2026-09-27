<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->date('booking_date');
            $table->string('slot_type');
            $table->unsignedTinyInteger('slot_number')->default(1);
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('procedure')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('mobile')->nullable();
            $table->string('booked_by')->nullable();
            $table->string('procedure_status')->default('ordered');
            // No FK constraint: waitlist_entries also references this table
            // (booking_id), so a hard constraint either direction would be
            // circular. Integrity is enforced at the application layer.
            $table->foreignId('waitlist_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['clinic_id', 'booking_date', 'slot_type', 'slot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_bookings');
    }
};
