<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTenant();
    }

    protected function setUpTenant()
    {
        // 1. Create Tenant (Using withoutEvents to bypass automatic DB creation)
        $this->tenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'test-tenant',
                'tenancy_db_name' => 'tenant_db',
                'company' => 'Test Company'
            ]);
        });

        // 2. Initialize Tenancy (This registers the tenant)
        tenancy()->initialize($this->tenant);

        // 3. Force In-Memory SQLite for the 'tenant' connection
        config([
            'database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]
        ]);

        DB::purge('tenant');

        // 4. Run Migrations on the Tenant Connection
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'tenant',
            '--force' => true,
        ]);

        if (!Schema::connection('tenant')->hasTable('projects')) {
            throw new \Exception('Tenant migrations failed to create projects table. Check migration path.');
        }
    }
}
