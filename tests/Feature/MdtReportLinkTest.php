<?php

namespace Tests\Feature;

use App\Models\ImagingReport;
use App\Models\MdtDiscussion;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MdtReportLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_can_be_linked_to_a_discussion(): void
    {
        $patient = Patient::factory()->create();
        $reports = ImagingReport::factory()->count(2)->for($patient)->create();
        $discussion = MdtDiscussion::factory()->for($patient)->create();

        $discussion->imagingReports()->sync($reports->pluck('id'));

        $this->assertCount(2, $discussion->fresh()->imagingReports);
        $this->assertSame(1, $reports->first()->fresh()->mdtDiscussions()->count());
    }

    public function test_deleting_a_report_removes_the_pivot_row(): void
    {
        $patient = Patient::factory()->create();
        $report = ImagingReport::factory()->for($patient)->create();
        $discussion = MdtDiscussion::factory()->for($patient)->create();
        $discussion->imagingReports()->attach($report);

        $report->forceDelete();

        $this->assertDatabaseMissing('imaging_report_mdt_discussion', [
            'mdt_discussion_id' => $discussion->id,
        ]);
    }
}
