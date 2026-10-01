<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A booking only holds a calendar slot; the procedure itself lives on the
// patient's chart as an Intervention or Imaging Report, linked here. Existing
// bookings get their chart record created now.
return new class extends Migration
{
    private const IMAGING = ['mri' => 'mri', 'ct' => 'ct', 'echo' => 'echo', 'tee' => 'echo_tee'];

    // Old bookings never recorded the exact procedure, so they get the broad
    // legacy types the Interventions tab already uses for imported records.
    private const INTERVENTION = ['surgery' => 'surgery', 'cath' => 'cath_intervention', 'ep' => 'ep_procedure'];

    public function up(): void
    {
        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->string('intervention_type')->nullable()->after('category');
            $table->foreignId('intervention_id')->nullable()->after('intervention_type')->constrained()->nullOnDelete();
            $table->foreignId('imaging_report_id')->nullable()->after('intervention_id')->constrained()->nullOnDelete();
        });

        // SQLite rebuilds the table to add foreign keys and drops the partial
        // index's WHERE clause; restate it so deleted bookings still free slots.
        DB::statement('DROP INDEX IF EXISTS procedure_bookings_live_slot_unique');
        DB::statement('CREATE UNIQUE INDEX procedure_bookings_live_slot_unique ON procedure_bookings (clinic_id, booking_date, slot_type, slot_number) WHERE deleted_at IS NULL');

        $now = Carbon::now();

        $bookings = DB::table('procedure_bookings')
            ->whereNull('deleted_at')
            ->whereNotNull('patient_id')
            ->whereIn('category', [...array_keys(self::IMAGING), ...array_keys(self::INTERVENTION)])
            ->get();

        foreach ($bookings as $booking) {
            if (isset(self::INTERVENTION[$booking->category])) {
                $id = DB::table('interventions')->insertGetId([
                    'clinic_id' => $booking->clinic_id,
                    'patient_id' => $booking->patient_id,
                    'date' => $booking->booking_date,
                    'procedure_status' => $booking->procedure_status,
                    'type' => self::INTERVENTION[$booking->category],
                    'name' => $booking->procedure ?: ucfirst($booking->category),
                    'operator_id' => $booking->staff_id,
                    'notes' => $booking->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('procedure_bookings')->where('id', $booking->id)->update(['intervention_id' => $id]);
            } else {
                $id = DB::table('imaging_reports')->insertGetId([
                    'clinic_id' => $booking->clinic_id,
                    'patient_id' => $booking->patient_id,
                    'type' => self::IMAGING[$booking->category],
                    'date' => $booking->booking_date,
                    'procedure_status' => $booking->procedure_status,
                    'report' => '',
                    'notes' => $booking->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('procedure_bookings')->where('id', $booking->id)->update(['imaging_report_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('procedure_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('imaging_report_id');
            $table->dropConstrainedForeignId('intervention_id');
            $table->dropColumn('intervention_type');
        });
    }
};
