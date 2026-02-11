<?php

use App\Models\User;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

use App\Notifications\TenantResetPassword;
use Illuminate\Support\Str;

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, TenantResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, TenantResetPassword::class, function ($notification) {
        // Extract token from URL
        // URL is something like http://localhost/reset-password/{token}?email=...
        $url = $notification->url;
        $path = parse_url($url, PHP_URL_PATH);
        $token = Arr::last(explode('/', $path));

        $response = $this->get($url); // Use the full URL directly

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, TenantResetPassword::class, function ($notification) use ($user) {
        $url = $notification->url;
        $path = parse_url($url, PHP_URL_PATH);
        $token = Arr::last(explode('/', $path));

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return true;
    });
});
