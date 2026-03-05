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
            $tenant = Tenant::create([
                'id' => 'test-tenant',
                'tenancy_db_name' => 'tenant_db',
                'company' => 'Test Company'
            ]);
            $tenant->domains()->create(['domain' => 'test.localhost']);
            return $tenant;
        });

        \Illuminate\Support\Facades\URL::forceRootUrl('http://test.localhost');

        // 2. Initialize Tenancy (This registers the tenant)
        tenancy()->initialize($this->tenant);

        // 3. Force In-Memory SQLite for the 'tenant' connection 
        // We alias it to the default sqlite connection so Central and Tenant models share the same schema during tests
        config([
            'database.connections.tenant' => config('database.connections.sqlite')
        ]);

        DB::purge('tenant');

        // 4. Run Migrations on the sqlite Connection
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'sqlite',
            '--force' => true,
        ]);

        if (!Schema::connection('sqlite')->hasTable('projects')) {
            throw new \Exception('Tenant migrations failed to create projects table. Check migration path.');
        }
    }
}
