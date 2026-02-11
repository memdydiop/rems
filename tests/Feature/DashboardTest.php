<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('central.dashboard'));
    $response->assertRedirect(route('central.login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('central.dashboard'));
    $response->assertOk();
});
