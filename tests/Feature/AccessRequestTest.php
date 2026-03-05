<?php

use Livewire\Livewire;

// RefreshDatabase is applied globally via Pest.php

test('landing page loads', function () {
    $this->get(route('central.home'))
        ->assertStatus(200)
        ->assertSee('PMS');
});

test('can register tenant', function () {
    Livewire::test('pages::landing')
        ->set('company', 'Big Real Estate')
        ->set('subdomain', 'bigre')
        ->set('name', 'John Doe')
        ->set('email', 'john@bigre.com')
        ->set('password', 'password123')
        ->call('registerTenant')
        ->assertHasNoErrors();
});

test('validation works', function () {
    Livewire::test('pages::landing')
        ->set('email', 'not-an-email')
        ->call('registerTenant')
        ->assertHasErrors(['email']);
});

test('landing page shows plans', function () {
    \App\Models\Plan::create(['name' => 'Starter', 'amount' => 0, 'interval' => 'monthly', 'currency' => 'usd', 'stripe_id' => 'p_1', 'paystack_code' => 'pln_1', 'description' => 'Desc', 'is_public' => true]);
    \App\Models\Plan::create(['name' => 'Croissance', 'amount' => 1000, 'interval' => 'monthly', 'currency' => 'usd', 'stripe_id' => 'p_2', 'paystack_code' => 'pln_2', 'description' => 'Desc', 'is_public' => true]);

    // Developer plan should be hidden (is_public = false)
    \App\Models\Plan::create(['name' => 'Developer', 'amount' => 0, 'interval' => 'monthly', 'currency' => 'usd', 'stripe_id' => 'p_dev', 'paystack_code' => 'pln_dev', 'description' => 'Desc', 'is_public' => false]);

    $this->get(route('central.home'))
        ->assertStatus(200)
        ->assertSee('Starter')
        ->assertSee('Croissance')
        ->assertDontSee('Developer');
});
