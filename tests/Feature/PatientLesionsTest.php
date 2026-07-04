<?php

namespace Tests\Feature;

use App\Enums\CardiacLesion;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientLesionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesions_are_filterable_with_json_contains(): void
    {
        $tof = Patient::factory()->create(['lesions' => ['tof', 'pda']]);
        Patient::factory()->create(['lesions' => ['vsd']]);
        Patient::factory()->create(['lesions' => null]);

        // Same grammar Laravel generates for pgsql jsonb
        $found = Patient::whereJsonContains('lesions', 'tof')->get();

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($tof));
    }

    public function test_lesion_enums_resolve_labels(): void
    {
        $patient = Patient::factory()->create(['lesions' => ['tof', 'hlhs']]);

        $labels = array_map(fn (?CardiacLesion $l) => $l?->getLabel(), $patient->lesionEnums());

        $this->assertSame(['TOF', 'HLHS'], $labels);
    }
}
