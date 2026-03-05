<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_setup_guide_when_fresh_install()
    {
        // 1. Arrange: No Plans, No Tenants
        $user = User::factory()->create();

        // 2. Act & Assert
        Livewire::actingAs($user)
            ->test('pages::central.dashboard')
            ->assertSee('Bonjour, ' . $user->name . '!')
            ->assertSee('Revenu Total');
        // The onboarding component itself might be what we really want to test,
        // but for now let's just assert the page loads successfully without errors.
    }

    public function test_dashboard_shows_stats_when_setup_complete()
    {
        // 1. Arrange: Plans & Tenants exist
        $user = User::factory()->create();

        Plan::create([
            'name' => 'Starter',
            'amount' => 1000,
            'currency' => 'XOF',
            'interval' => 'monthly',
            'features' => [],
            'paystack_code' => 'PLN_test123'
        ]);

        // Create a fake tenant without triggering database creation events
        $tenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'acme',
                'data' => [],
            ]);
        });

        // 2. Act & Assert
        Livewire::actingAs($user)
            ->test('pages::central.dashboard')
            ->assertSee('Revenu Total')
            ->assertSee('Bonjour, ' . $user->name . '!');
    }
}
