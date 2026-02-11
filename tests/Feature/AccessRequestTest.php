<?php

use Livewire\Livewire;

// RefreshDatabase is applied globally via Pest.php

test('landing page loads', function () {
    $this->get(route('central.home'))
        ->assertStatus(200)
        ->assertSee('Propella');
});

test('can request access', function () {
    Livewire::test('pages::central.landing')
        ->set('company', 'Big Real Estate')
        ->set('name', 'John Doe')
        ->set('email', 'john@bigre.com')
        ->call('requestAccess')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSee('Demande envoyée');

    $this->assertDatabaseHas('leads', [
        'email' => 'john@bigre.com',
        'company' => 'Big Real Estate',
        'status' => 'pending',
    ]);
});

test('validation works', function () {
    Livewire::test('pages::central.landing')
        ->set('email', 'not-an-email')
        ->call('requestAccess')
        ->assertHasErrors(['email']);
});
test('landing page shows plans', function () {
    \App\Models\Plan::create(['name' => 'Starter', 'amount' => 0, 'interval' => 'monthly', 'currency' => 'usd', 'stripe_id' => 'p_1', 'paystack_code' => 'pln_1', 'description' => 'Desc']);
    \App\Models\Plan::create(['name' => 'Croissance', 'amount' => 1000, 'interval' => 'monthly', 'currency' => 'usd', 'stripe_id' => 'p_2', 'paystack_code' => 'pln_2', 'description' => 'Desc']);

    // Developer plan should be hidden (not in the allowed list)
    \App\Models\Plan::create(['name' => 'Developer', 'amount' => 0, 'interval' => 'monthly', 'currency' => 'usd', 'stripe_id' => 'p_dev', 'paystack_code' => 'pln_dev', 'description' => 'Desc']);

    $this->get(route('central.home'))
        ->assertStatus(200)
        ->assertSee('Starter')
        ->assertSee('Croissance')
        ->assertDontSee('Developer');
});
