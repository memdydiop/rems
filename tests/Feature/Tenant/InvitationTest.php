<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Notifications\TenantInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Tenant with memory DB
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'tenancy_db_name' => ':memory:',
            'company' => 'Test Company'
        ]);
        $tenant->domains()->create(['domain' => 'to-be-overridden']);

        // Initialize Tenancy
        tenancy()->initialize($tenant);

        // Migrate Tenant Tables
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_admin_can_invite_member()
    {
        Notification::fake();

        $admin = User::factory()->create(['email' => 'admin@example.com']);

        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole('admin');

        Volt::actingAs($admin)
            ->test('pages.tenant.settings.members')
            ->set('email', 'colleague@example.com')
            ->set('role', 'member')
            ->call('invite');

        $this->assertDatabaseHas('tenant_invitations', ['email' => 'colleague@example.com']);

        Notification::assertSentTo(
            new \Illuminate\Support\AnonymousNotifiable,
            TenantInvitationNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'colleague@example.com';
            }
        );
    }
}
