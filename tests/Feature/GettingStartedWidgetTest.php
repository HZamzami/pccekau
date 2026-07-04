<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GettingStartedWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_is_shown_on_a_fresh_install(): void
    {
        $user = User::factory()->create(['role' => UserRole::Doctor]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Getting started with PCCEKAU');
    }

    public function test_checklist_is_hidden_after_dismissal(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Doctor,
            'getting_started_dismissed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Getting started with PCCEKAU');
    }
}
