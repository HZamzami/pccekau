<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Clinic;
use App\Models\ClinicVisit;
use App\Models\ImagingReport;
use App\Models\MdtDiscussion;
use App\Models\Patient;
use App\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Seeding runs in console where no tenant is bound; pick the first
        // clinic (or create a demo one) so clinic_id auto-fills.
        Filament::setTenant(Clinic::first() ?? Clinic::factory()->create(['name' => 'Demo Clinic', 'slug' => 'demo']), isQuiet: true);

        $consultants = Staff::factory()->count(5)->create(['role' => 'consultant']);
        $fellows = Staff::factory()->count(4)->create(['role' => 'fellow']);
        $allStaff = $consultants->concat($fellows);

        Patient::factory()->count(25)->create()->each(function (Patient $patient) use ($allStaff) {
            ImagingReport::factory()
                ->count(fake()->numberBetween(1, 4))
                ->for($patient)
                ->create([
                    'status' => fake()->randomElement(ReportStatus::cases()),
                ])
                ->each(function (ImagingReport $report) use ($allStaff) {
                    $report->performers()->attach($allStaff->random()->id);

                    if (fake()->boolean(70)) {
                        $report->readers()->attach($allStaff->random()->id);
                    }

                    if ($report->status === ReportStatus::Final) {
                        $report->hasSigner()
                            ? $report->updateQuietly(['finalized_at' => $report->date->addDays(2)])
                            : $report->updateQuietly(['status' => ReportStatus::Preliminary]);
                    }
                });

            ClinicVisit::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($patient)
                ->create([
                    'seen_by_id' => $allStaff->random()->id,
                    // Mix of overdue, due-today, and upcoming follow-ups
                    'next_follow_up_date' => fake()->randomElement([
                        today()->subDays(fake()->numberBetween(1, 30)),
                        today(),
                        today()->addDays(fake()->numberBetween(1, 60)),
                        null,
                    ]),
                ]);

            if (fake()->boolean(40)) {
                MdtDiscussion::create([
                    'patient_id' => $patient->id,
                    'discussion_date' => fake()->randomElement([today(), today()->addDays(3), today()->subWeeks(2)]),
                    'weight_kg' => $patient->weight_kg,
                    'oxygen_saturation' => fake()->numberBetween(75, 100),
                    'diagnosis' => $patient->primary_diagnosis,
                    'reason_for_discussion' => fake()->sentence(),
                    'history' => fake()->paragraph(),
                    'exam_findings' => fake()->sentence(),
                    'echo_findings' => fake()->paragraph(),
                    'specialist_fellow_id' => $allStaff->random()->id,
                    'discussion_results' => fake()->paragraph(),
                ]);
            }
        });
    }
}
