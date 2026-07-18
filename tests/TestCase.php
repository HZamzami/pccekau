<?php

namespace Tests;

use App\Models\Clinic;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** The tenant every factory record and Livewire test runs against. */
    protected ?Clinic $clinic = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Multi-tenancy: give database-backed tests a current tenant so the
        // BelongsToClinic creating-hook fills clinic_id and Livewire
        // component tests resolve tenant-aware URLs.
        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->clinic = Clinic::factory()->create();
            // isQuiet: the TenantSet event requires an authenticated user,
            // which doesn't exist yet at setUp time.
            Filament::setTenant($this->clinic, isQuiet: true);
        }
    }
}
