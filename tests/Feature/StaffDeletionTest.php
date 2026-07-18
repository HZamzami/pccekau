<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\StaffResource\Pages\EditStaff;
use App\Filament\Resources\StaffResource\Pages\ListStaff;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_staff_from_the_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $staff = Staff::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListStaff::class)
            ->assertTableActionVisible('delete', $staff)
            ->callTableAction('delete', $staff)
            ->assertSuccessful();

        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);
    }

    public function test_admin_can_delete_staff_from_the_edit_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $staff = Staff::factory()->create();

        Livewire::actingAs($admin)
            ->test(EditStaff::class, ['record' => $staff->getRouteKey()])
            ->assertActionVisible('delete')
            ->callAction('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('staff', ['id' => $staff->id]);
    }

    public function test_delete_is_hidden_from_non_admins(): void
    {
        $doctor = User::factory()->create(['role' => UserRole::Doctor]);
        $staff = Staff::factory()->create();

        Livewire::actingAs($doctor)
            ->test(ListStaff::class)
            ->assertTableActionHidden('delete', $staff);

        Livewire::actingAs($doctor)
            ->test(EditStaff::class, ['record' => $staff->getRouteKey()])
            ->assertActionHidden('delete');
    }
}
