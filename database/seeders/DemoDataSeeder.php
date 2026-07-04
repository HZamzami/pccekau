<?php

namespace Database\Seeders;

use App\Enums\ImagingType;
use App\Enums\ReportStatus;
use App\Models\ClinicVisit;
use App\Models\ImagingReport;
use App\Models\MdtDiscussion;
use App\Models\Patient;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $consultants = Staff::factory()->count(5)->create(['role' => 'consultant']);
        $fellows = Staff::factory()->count(4)->create(['role' => 'fellow']);
        $allStaff = $consultants->concat($fellows);

        Patient::factory()->count(25)->create()->each(function (Patient $patient) use ($allStaff) {
            ImagingReport::factory()
                ->count(fake()->numberBetween(1, 4))
                ->for($patient)
                ->create([
                    'status' => fake()->randomElement(ReportStatus::cases()),
                    'performed_by_id' => $allStaff->random()->id,
                    'signed_by' => fake()->boolean(70) ? $allStaff->random()->id : null,
                ])
                ->each(function (ImagingReport $report) {
                    if ($report->status === ReportStatus::Final) {
                        $report->signed_by
                            ? $report->updateQuietly(['finalized_at' => $report->date->addDays(2)])
                            : $report->updateQuietly(['status' => ReportStatus::Preliminary]);
                    }

                    if ($report->type->isEcho()) {
                        $height = fake()->randomFloat(1, 50, 160);
                        $weight = fake()->randomFloat(2, 4, 55);
                        $report->echoMeasurement()->create([
                            'height_cm' => $height,
                            'weight_kg' => $weight,
                            'ivsd' => fake()->randomFloat(2, 0.4, 1.0),
                            'lvidd' => fake()->randomFloat(2, 2.0, 4.8),
                            'lvpwd' => fake()->randomFloat(2, 0.4, 1.0),
                            'lvids' => fake()->randomFloat(2, 1.4, 3.2),
                            'la' => fake()->randomFloat(2, 1.5, 3.5),
                            'ao_annulus' => fake()->randomFloat(2, 1.0, 2.2),
                            'ao_root' => fake()->randomFloat(2, 1.4, 2.8),
                            'ef' => fake()->randomFloat(1, 55, 75),
                            'fs' => fake()->randomFloat(1, 28, 45),
                        ]);
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
                    'oxygen_saturation' => $patient->baseline_oxygen_saturation,
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
