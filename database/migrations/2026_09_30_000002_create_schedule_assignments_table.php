<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Every coverage and consultant role is now assigned per day (Sun–Sat)
// instead of one doctor for the whole week. Existing weekly assignments are
// expanded to all 7 days; the old per-week columns are then dropped.
return new class extends Migration
{
    private const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    private const ONCALL_WEEKLY = [
        'clinic_staff_id' => 'clinic',
        'inpatient_staff_id' => 'inpatient',
        'consultation_staff_id' => 'consultation',
        'cath_staff_id' => 'cath',
    ];

    private const CONSULTANT_WEEKLY = [
        'service_staff_id' => 'consultant_service',
        'cath_staff_id' => 'consultant_cath',
        'ep_staff_id' => 'consultant_ep',
    ];

    public function up(): void
    {
        Schema::create('schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('oncall_schedule_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('consultant_schedule_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->string('role');
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();

            $table->unique(['oncall_schedule_id', 'day', 'role']);
            $table->unique(['consultant_schedule_id', 'day', 'role']);
            $table->index(['staff_id', 'role']);
        });

        $now = Carbon::now();

        foreach (DB::table('oncall_schedules')->get() as $week) {
            $rows = [];

            foreach (self::ONCALL_WEEKLY as $column => $role) {
                if ($week->$column) {
                    foreach (range(0, 6) as $day) {
                        $rows[] = $this->row($week, 'oncall_schedule_id', $day, $role, $week->$column, $now);
                    }
                }
            }

            foreach (self::DAYS as $day => $name) {
                if ($staffId = $week->{"oncall_{$name}_id"}) {
                    $rows[] = $this->row($week, 'oncall_schedule_id', $day, 'oncall', $staffId, $now);
                }
            }

            DB::table('schedule_assignments')->insert($rows);
        }

        foreach (DB::table('consultant_schedules')->get() as $week) {
            $rows = [];

            foreach (self::CONSULTANT_WEEKLY as $column => $role) {
                if ($week->$column) {
                    foreach (range(0, 6) as $day) {
                        $rows[] = $this->row($week, 'consultant_schedule_id', $day, $role, $week->$column, $now);
                    }
                }
            }

            DB::table('schedule_assignments')->insert($rows);
        }

        $oncallColumns = [...array_keys(self::ONCALL_WEEKLY), ...array_map(fn ($d) => "oncall_{$d}_id", self::DAYS)];

        Schema::table('oncall_schedules', function (Blueprint $table) use ($oncallColumns) {
            foreach ($oncallColumns as $column) {
                $table->dropForeign([$column]);
            }
            $table->dropColumn($oncallColumns);
        });

        Schema::table('consultant_schedules', function (Blueprint $table) {
            foreach (array_keys(self::CONSULTANT_WEEKLY) as $column) {
                $table->dropForeign([$column]);
            }
            $table->dropColumn(array_keys(self::CONSULTANT_WEEKLY));
        });
    }

    public function down(): void
    {
        Schema::table('oncall_schedules', function (Blueprint $table) {
            foreach ([...array_keys(self::ONCALL_WEEKLY), ...array_map(fn ($d) => "oncall_{$d}_id", self::DAYS)] as $column) {
                $table->foreignId($column)->nullable()->constrained('staff')->nullOnDelete();
            }
        });

        Schema::table('consultant_schedules', function (Blueprint $table) {
            foreach (array_keys(self::CONSULTANT_WEEKLY) as $column) {
                $table->foreignId($column)->nullable()->constrained('staff')->nullOnDelete();
            }
        });

        // Weekly columns can only hold one doctor, so Sunday's assignment wins.
        foreach (DB::table('schedule_assignments')->get() as $a) {
            if ($a->oncall_schedule_id) {
                $column = $a->role === 'oncall'
                    ? 'oncall_'.self::DAYS[(int) $a->day].'_id'
                    : ((int) $a->day === 0 ? array_search($a->role, self::ONCALL_WEEKLY, true) : null);

                if ($column) {
                    DB::table('oncall_schedules')->where('id', $a->oncall_schedule_id)->update([$column => $a->staff_id]);
                }
            } elseif ($a->consultant_schedule_id && (int) $a->day === 0) {
                $column = array_search($a->role, self::CONSULTANT_WEEKLY, true);
                DB::table('consultant_schedules')->where('id', $a->consultant_schedule_id)->update([$column => $a->staff_id]);
            }
        }

        Schema::dropIfExists('schedule_assignments');
    }

    private function row(object $week, string $fk, int $day, string $role, int $staffId, Carbon $now): array
    {
        return [
            'clinic_id' => $week->clinic_id,
            $fk => $week->id,
            'day' => $day,
            'role' => $role,
            'staff_id' => $staffId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
};
