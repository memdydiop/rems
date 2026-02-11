<?php

use App\Models\Tenant;
use App\Models\User;
use Livewire\Livewire;

// RefreshDatabase is applied globally via Pest.php

test('tenant management table renders', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::central.tenants.index')
        ->assertStatus(200)
        ->assertSee('All Workspaces');
});

test('can search tenants', function () {
    $user = User::factory()->create();

    // Create tenants without triggering database creation events
    // We only need the records in the central database for this test
    Tenant::withoutEvents(function () {
        Tenant::create([
            'id' => 'alpha',
            'tenancy_db_name' => 'tenant_alpha',
            'company' => 'Alpha Inc'
        ]);

        Tenant::create([
            'id' => 'beta',
            'tenancy_db_name' => 'tenant_beta',
            'company' => 'Beta Corp'
        ]);
    });

    Livewire::actingAs($user)
        ->test('pages::central.tenants.index')
        ->set('search', 'alpha')
        ->assertSee('Alpha Inc') // Checking company name which is likely displayed
        ->assertDontSee('Beta Corp');
});

test('tenant details page renders with subscription info', function () {
    $user = User::factory()->create();

    // Create Plan
    $plan = \App\Models\Plan::create([
        'name' => 'Pro Plan',
        'paystack_code' => 'PLN_123456',
        'amount' => 5000,
        'currency' => 'USD',
        'interval' => 'month',
        'description' => 'Pro features',
        'features' => [
            'users' => 5,
            'storage' => 10,
            'premium_support' => true,
        ],
    ]);

    // Create Tenant
    $tenant = Tenant::withoutEvents(function () use ($plan) {
        $t = Tenant::create([
            'id' => 'gamma',
            'tenancy_db_name' => 'tenant_gamma',
            'company' => 'Gamma Ltd'
        ]);

        // Add Domain
        $t->domains()->create(['domain' => 'gamma.test']);

        // Add Subscription
        \App\Models\Subscription::create([
            'tenant_id' => $t->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        return $t;
    });

    Livewire::actingAs($user)
        ->test('pages::central.tenants.show', ['tenant' => $tenant])
        ->assertStatus(200)
        ->assertSee('Gamma Ltd')
        ->assertSee('Pro Plan')
        ->assertSee('Actif') // Status label
        ->assertSee('users')
        ->assertSee('5')
        ->assertSee('storage')
        ->assertSee('10')
        ->assertSee('premium support')
        ->assertSee('gamma.test');
});
