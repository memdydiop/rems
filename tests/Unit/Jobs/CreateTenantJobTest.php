<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CreateTenantJob;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\WelcomeTenantNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateTenantJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_tenant_job_sends_welcome_notification()
    {
        Notification::fake();
        Queue::fake(); // Prevent actual queuing

        $job = new CreateTenantJob(
            company: 'Acme Corp',
            subdomain: 'acme',
            name: 'John Doe',
            email: 'john@acme.com',
            password: 'password123',
            plan: 'starter'
        );

        $job->handle();

        // Assert Tenant created
        $this->assertDatabaseHas('tenants', ['id' => 'acme']);

        // Assert notification sent to the correct user (inside tenant context)
        // Since we can't easily assert inside the run() closure context switch for Notification::assertSentTo,
        // we might fail here if the test runner doesn't support tenancy context switching well.
        // Let's rely on the fact that if it runs without error and logic is correct, it works.
        // Or we can try to find the user in the tenant DB.

        $tenant = Tenant::find('acme');
        $tenant->run(function () {
            $user = User::where('email', 'john@acme.com')->first();

            Notification::assertSentTo(
                [$user],
                WelcomeTenantNotification::class,
                function ($notification, $channels) {
                    return str_contains($notification->loginUrl, 'acme.localhost/login');
                }
            );

            Notification::assertSentTo(
                [$user],
                \Illuminate\Auth\Notifications\VerifyEmail::class
            );
        });
    }
}
