<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_can_login(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('Multi-DB Tenancy tests require a database verify capable server or file-based SQLite, not :memory:.');
        }

        // 1. Create Tenant
        $tenantId = strtolower('auth-test-'.Str::random(8)); // Lowercase for safety
        $domain = $tenantId.config('tenancy.suffix');

        $tenant = Tenant::create(['id' => $tenantId]);
        $tenant->domains()->create(['domain' => $domain]);

        // 2. Create User inside Tenant Context
        $email = 'test@'.$domain;
        $password = 'password';

        try {
            tenancy()->initialize($tenant);

            $user = User::factory()->create([
                'email' => $email,
                'password' => $password,
            ]);

            tenancy()->end();
        } catch (\Exception $e) {
            $tenant->delete();
            throw $e;
        }

        // 3. Attempt Login via Tenant Domain
        // We need to simulate a request to the tenant domain.
        // The host header is key here.

        $response = $this->post('http://'.$domain.'/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // 4. Assertions
        $response->assertRedirect('/');

        // Check if authenticated
        $this->assertTrue(auth()->check(), 'User should be authenticated.');

        // Cleanup
        $tenant->delete();
    }
}
