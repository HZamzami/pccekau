<?php

namespace Tests\Feature;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Enums\WaitlistPriority;
use App\Filament\Resources\ProcedureBookingResource\Pages\ListProcedureBookings;
use App\Filament\Resources\WaitlistEntryResource\Pages\CreateWaitlistEntry;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Filament\Widgets\WeeklySurgeriesCathWidget;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryTabsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => UserRole::Doctor]);
    }

    private function entry(ProcedureCategory $category, int $priority = 3, ?Carbon $createdAt = null): WaitlistEntry
    {
        $entry = WaitlistEntry::create([
            'patient_id' => Patient::factory()->create()->id,
            'category' => $category,
            'priority' => $priority,
        ]);

        if ($createdAt) {
            $entry->forceFill(['created_at' => $createdAt])->save();
        }

        return $entry;
    }

    private function booking(ProcedureCategory $category, array $attributes = []): ProcedureBooking
    {
        return ProcedureBooking::create([
            'patient_id' => Patient::factory()->create()->id,
            'booking_date' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->addDay(),
            'category' => $category,
            'procedure_status' => ProcedureStatus::Scheduled,
            ...$attributes,
        ]);
    }

    public function test_new_waitlist_entries_default_to_priority_3(): void
    {
        Livewire::actingAs($this->doctor)
            ->test(CreateWaitlistEntry::class)
            ->assertFormSet(['priority' => WaitlistPriority::P3]);
    }

    public function test_waitlist_tabs_show_one_category_each_most_urgent_first(): void
    {
        $surgery = $this->entry(ProcedureCategory::Surgery);
        $cath = $this->entry(ProcedureCategory::Cath);
        $echo = $this->entry(ProcedureCategory::Echo);

        $page = Livewire::actingAs($this->doctor)->test(ListWaitlistEntries::class);

        $this->assertSame(['all', 'surgery', 'cath', 'ep', 'mri', 'ct', 'echo_3d'], array_keys($page->instance()->getTabs()));
        $page->assertCanSeeTableRecords([$surgery, $cath, $echo]);

        $page->set('activeTab', 'surgery')
            ->assertCanSeeTableRecords([$surgery])
            ->assertCanNotSeeTableRecords([$cath, $echo]);

        $oldRoutine = $this->entry(ProcedureCategory::Cath, 3, now()->subMonth());
        $newUrgent = $this->entry(ProcedureCategory::Cath, 1);

        Livewire::actingAs($this->doctor)->test(ListWaitlistEntries::class)
            ->set('activeTab', 'cath')
            ->assertCanSeeTableRecords([$newUrgent, $oldRoutine, $cath], inOrder: true);
    }

    public function test_booking_tabs_filter_by_category_and_status(): void
    {
        $surgery = $this->booking(ProcedureCategory::Surgery);
        $postponed = $this->booking(ProcedureCategory::Surgery, ['procedure_status' => ProcedureStatus::Postponed]);
        $completed = $this->booking(ProcedureCategory::Surgery, ['procedure_status' => ProcedureStatus::Completed]);
        $cath = $this->booking(ProcedureCategory::Cath);

        Livewire::actingAs($this->doctor)->test(ListProcedureBookings::class)
            ->set('activeTab', 'surgery')
            ->assertCanSeeTableRecords([$surgery, $postponed, $completed])
            ->assertCanNotSeeTableRecords([$cath])
            ->filterTable('procedure_status', [ProcedureStatus::Scheduled->value, ProcedureStatus::Postponed->value])
            ->assertCanSeeTableRecords([$surgery, $postponed])
            ->assertCanNotSeeTableRecords([$completed, $cath]);

        // Several categories at once on the All tab.
        $mri = $this->booking(ProcedureCategory::Mri);

        Livewire::actingAs($this->doctor)->test(ListProcedureBookings::class)
            ->filterTable('category', ['surgery', 'cath'])
            ->assertCanSeeTableRecords([$surgery, $cath])
            ->assertCanNotSeeTableRecords([$mri]);
    }

    public function test_dashboard_lists_this_weeks_surgeries_and_caths(): void
    {
        $surgery = $this->booking(ProcedureCategory::Surgery, ['procedure' => 'Glenn shunt']);
        $cath = $this->booking(ProcedureCategory::Cath, ['procedure' => 'PDA closure']);
        $this->booking(ProcedureCategory::Surgery, ['procedure' => 'Cancelled op', 'procedure_status' => ProcedureStatus::Cancelled]);
        $this->booking(ProcedureCategory::Surgery, ['procedure' => 'Next week op', 'booking_date' => now()->addWeeks(2)]);
        $this->booking(ProcedureCategory::Mri, ['procedure' => 'Cardiac MRI']);

        Livewire::actingAs($this->doctor)->test(WeeklySurgeriesCathWidget::class)
            ->assertSee(['Surgeries this week', 'Caths this week', 'Glenn shunt', 'PDA closure'])
            ->assertDontSee(['Cancelled op', 'Next week op', 'Cardiac MRI']);

        $this->actingAs($this->doctor)->get(Dashboard::getUrl())->assertOk()
            ->assertSeeLivewire(WeeklySurgeriesCathWidget::class)
            ->assertDontSee("Today's Coverage");
    }
}
