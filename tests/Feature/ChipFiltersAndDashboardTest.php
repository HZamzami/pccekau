<?php

namespace Tests\Feature;

use App\Enums\ProcedureCategory;
use App\Enums\ProcedureStatus;
use App\Enums\UserRole;
use App\Enums\WaitlistPriority;
use App\Enums\WaitlistStatus;
use App\Filament\Pages\ProcedureCalendarPage;
use App\Filament\Resources\ProcedureBookingResource\Pages\ListProcedureBookings;
use App\Filament\Resources\WaitlistEntryResource\Pages\CreateWaitlistEntry;
use App\Filament\Resources\WaitlistEntryResource\Pages\ListWaitlistEntries;
use App\Filament\Widgets\TodayOncallWidget;
use App\Filament\Widgets\UrgentWaitlistWidget;
use App\Filament\Widgets\WeeklyCathWidget;
use App\Filament\Widgets\WeeklySurgeriesWidget;
use App\Models\Patient;
use App\Models\ProcedureBooking;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChipFiltersAndDashboardTest extends TestCase
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

    public function test_waitlist_category_chips_filter_and_most_urgent_comes_first(): void
    {
        $surgery = $this->entry(ProcedureCategory::Surgery);
        $cath = $this->entry(ProcedureCategory::Cath);
        $echo = $this->entry(ProcedureCategory::Echo);

        Livewire::actingAs($this->doctor)->test(ListWaitlistEntries::class)
            ->assertCanSeeTableRecords([$surgery, $cath, $echo])
            ->filterTable('category', ['surgery'])
            ->assertCanSeeTableRecords([$surgery])
            ->assertCanNotSeeTableRecords([$cath, $echo]);

        $oldRoutine = $this->entry(ProcedureCategory::Cath, 3, now()->subMonth());
        $newUrgent = $this->entry(ProcedureCategory::Cath, 1);

        Livewire::actingAs($this->doctor)->test(ListWaitlistEntries::class)
            ->filterTable('category', ['cath'])
            ->assertCanSeeTableRecords([$newUrgent, $oldRoutine, $cath], inOrder: true);
    }

    public function test_booking_chips_filter_by_category_and_status(): void
    {
        $surgery = $this->booking(ProcedureCategory::Surgery);
        $postponed = $this->booking(ProcedureCategory::Surgery, ['procedure_status' => ProcedureStatus::Postponed]);
        $completed = $this->booking(ProcedureCategory::Surgery, ['procedure_status' => ProcedureStatus::Completed]);
        $cath = $this->booking(ProcedureCategory::Cath);
        $mri = $this->booking(ProcedureCategory::Mri);

        Livewire::actingAs($this->doctor)->test(ListProcedureBookings::class)
            ->filterTable('category', ['surgery'])
            ->assertCanSeeTableRecords([$surgery, $postponed, $completed])
            ->assertCanNotSeeTableRecords([$cath, $mri])
            ->filterTable('procedure_status', [ProcedureStatus::Scheduled->value, ProcedureStatus::Postponed->value])
            ->assertCanSeeTableRecords([$surgery, $postponed])
            ->assertCanNotSeeTableRecords([$completed, $cath, $mri]);

        Livewire::actingAs($this->doctor)->test(ListProcedureBookings::class)
            ->filterTable('category', ['surgery', 'cath'])
            ->assertCanSeeTableRecords([$surgery, $cath])
            ->assertCanNotSeeTableRecords([$mri]);
    }

    public function test_filter_bar_shows_quick_chips_then_pills(): void
    {
        $this->actingAs($this->doctor)
            ->get(ListProcedureBookings::getUrl())
            ->assertOk()
            ->assertSee(['pc-quick', 'pc-pill', '.pc-chip:has(input:checked)'], false)
            // Category is pinned as chips, main six first; the rest are pills.
            ->assertSeeTextInOrder(['Surgery', 'Cath', 'EP (invasive)', 'MRI', 'CT', '3D Echo', '+8 more', 'Status', 'Slot', 'Consultant', 'Date']);
    }

    public function test_a_set_pill_shows_its_value(): void
    {
        Livewire::actingAs($this->doctor)->test(ListProcedureBookings::class)
            ->filterTable('procedure_status', [ProcedureStatus::Scheduled->value, ProcedureStatus::Postponed->value])
            ->assertSee('Scheduled, Postponed')
            ->filterTable('booking_date', ['from' => '2026-11-01', 'until' => '2026-11-07'])
            ->assertSee('01 Nov – 07 Nov');
    }

    public function test_date_range_filter_narrows_bookings(): void
    {
        $thisWeek = $this->booking(ProcedureCategory::Cath);
        $later = $this->booking(ProcedureCategory::Cath, ['booking_date' => now()->addMonths(2)]);

        Livewire::actingAs($this->doctor)->test(ListProcedureBookings::class)
            ->filterTable('booking_date', ['until' => now()->addWeek()->toDateString()])
            ->assertCanSeeTableRecords([$thisWeek])
            ->assertCanNotSeeTableRecords([$later]);
    }

    public function test_calendar_uses_the_same_bar(): void
    {
        Livewire::actingAs($this->doctor)->test(ProcedureCalendarPage::class)
            ->assertSee(['pc-bar', 'pc-quick', 'pc-pill'], false)
            ->set('categories', ['surgery', 'cath'])
            ->set('statuses', [ProcedureStatus::Scheduled->value])
            ->assertSee('Clear all')
            ->call('clearFilters')
            ->assertSet('categories', [])
            ->assertSet('statuses', []);
    }

    public function test_dashboard_lists_this_weeks_surgeries_and_caths(): void
    {
        $surgery = $this->booking(ProcedureCategory::Surgery, ['procedure' => 'Glenn shunt']);
        $cath = $this->booking(ProcedureCategory::Cath, ['procedure' => 'PDA closure']);
        $cancelled = $this->booking(ProcedureCategory::Surgery, ['procedure' => 'Cancelled op', 'procedure_status' => ProcedureStatus::Cancelled]);
        $nextWeek = $this->booking(ProcedureCategory::Surgery, ['procedure' => 'Next week op', 'booking_date' => now()->addWeeks(2)]);

        Livewire::actingAs($this->doctor)->test(WeeklySurgeriesWidget::class)
            ->assertSee('Surgeries this week')
            ->assertCanSeeTableRecords([$surgery])
            ->assertCanNotSeeTableRecords([$cath, $cancelled, $nextWeek]);

        Livewire::actingAs($this->doctor)->test(WeeklyCathWidget::class)
            ->assertSee('Caths this week')
            ->assertCanSeeTableRecords([$cath])
            ->assertCanNotSeeTableRecords([$surgery]);

        $this->actingAs($this->doctor)->get(Dashboard::getUrl())->assertOk()
            ->assertSeeLivewire(WeeklySurgeriesWidget::class)
            ->assertSeeLivewire(WeeklyCathWidget::class)
            ->assertSeeLivewire(UrgentWaitlistWidget::class)
            ->assertSeeLivewire(TodayOncallWidget::class);
    }

    public function test_urgent_waitlist_widget_puts_priority_1_first(): void
    {
        $routine = $this->entry(ProcedureCategory::Cath, 3, now()->subMonth());
        $urgent = $this->entry(ProcedureCategory::Surgery, 1);
        $scheduled = $this->entry(ProcedureCategory::Mri, 1);
        $scheduled->update(['status' => WaitlistStatus::Scheduled]);

        Livewire::actingAs($this->doctor)->test(UrgentWaitlistWidget::class)
            ->assertCanSeeTableRecords([$urgent, $routine], inOrder: true)
            ->assertCanNotSeeTableRecords([$scheduled]);
    }
}
