<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per fellow per block (3 fellows × 13 blocks = 39 rows per year)
        Schema::create('fellows_rotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('block_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('fellow_name');
            $table->enum('rotation', [
                'echo', 'ep', 'cath', 'opd', 'icu',
                'inpatient', 'research', 'achd', 'imaging',
                'elective', 'vacation',
            ]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fellows_rotations');
    }
};
