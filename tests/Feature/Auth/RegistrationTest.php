<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('central.register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('central.register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('central.dashboard', absolute: false));

    $this->assertAuthenticated();
});
