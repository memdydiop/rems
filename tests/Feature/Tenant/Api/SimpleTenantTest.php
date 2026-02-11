<?php

namespace Tests\Feature\Tenant\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SimpleTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created_and_initialized()
    {
        $tenantId = strtolower('simple-' . Str::random(4));
        $tenant = Tenant::create(['id' => $tenantId]);

        $this->assertDatabaseHas('tenants', ['id' => $tenantId]);

        // Initialize checks connection switching
        tenancy()->initialize($tenant);
        $this->assertTrue(true);
        tenancy()->end();

        $tenant->delete();
    }
}
