<?php

namespace Tests\Feature;

use App\Filament\Widgets\UpcomingMdtWidget;
use App\Models\MdtDiscussion;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UpcomingMdtWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_renders_the_specialist_fellow_name(): void
    {
        $this->actingAs(User::factory()->create());

        $fellow = Staff::factory()->create(['name' => 'Dr. Widget Regression']);
        MdtDiscussion::factory()->create([
            'discussion_date' => today()->addDays(2),
            'specialist_fellow_id' => $fellow->id,
        ]);

        Livewire::test(UpcomingMdtWidget::class)
            ->assertSee('Dr. Widget Regression');
    }

    public function test_past_discussions_are_excluded(): void
    {
        $this->actingAs(User::factory()->create());

        MdtDiscussion::factory()->create([
            'discussion_date' => today()->subWeek(),
            'diagnosis' => 'PastCaseDiagnosis',
        ]);

        Livewire::test(UpcomingMdtWidget::class)
            ->assertDontSee('PastCaseDiagnosis');
    }
}
