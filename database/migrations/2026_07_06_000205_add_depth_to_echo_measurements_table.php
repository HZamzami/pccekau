<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echo_measurements', function (Blueprint $table) {
            // RV / function
            $table->decimal('tapse', 4, 2)->nullable()->after('fs');
            $table->string('rv_function')->nullable()->after('tapse');

            // Valve regurgitation grades
            foreach (['mv', 'tv', 'av', 'pv'] as $valve) {
                $table->string("{$valve}_regurg")->nullable();
            }

            // Mean gradients (clinically preferred for inflow/outflow assessment)
            $table->decimal('mv_mean_gradient', 5, 2)->nullable();
            $table->decimal('av_mean_gradient', 5, 2)->nullable();

            // Arch & shunts
            $table->decimal('coarct_peak_gradient', 5, 2)->nullable();
            $table->decimal('coarct_mean_gradient', 5, 2)->nullable();
            $table->decimal('pda_size_mm', 4, 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('echo_measurements', function (Blueprint $table) {
            $table->dropColumn([
                'tapse',
                'rv_function',
                'mv_regurg',
                'tv_regurg',
                'av_regurg',
                'pv_regurg',
                'mv_mean_gradient',
                'av_mean_gradient',
                'coarct_peak_gradient',
                'coarct_mean_gradient',
                'pda_size_mm',
            ]);
        });
    }
};
