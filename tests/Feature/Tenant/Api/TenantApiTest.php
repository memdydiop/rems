<?php

namespace Tests\Feature\Tenant\Api;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Renter;
use App\Models\Lease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_full_flow()
    {
        // Override tenant connection template for testing
        config([
            'database.connections.tenant_template' => [
                'driver' => 'sqlite',
                'foreign_key_constraints' => true,
            ]
        ]);

        // 1. Create Tenant & Domain
        $tenantId = strtolower('api-flow-file-' . Str::random(8));
        $domain = $tenantId . config('tenancy.suffix');

        $dbName = 'test_tenant_' . $tenantId . '.sqlite';
        $dbPath = database_path($dbName);
        touch($dbPath);

        // Create Tenant with file-based DB
        $tenant = new Tenant([
            'id' => $tenantId,
            'tenancy_db_name' => $dbName,
        ]);
        $tenant->saveQuietly();

        $tenant->createDomain(['domain' => $domain]);

        // Initialize Tenancy
        tenancy()->initialize($tenant);

        // Migrate Tenant Tables
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);

        $user = User::factory()->create([
            'email' => 'flow@test.com',
            'password' => bcrypt('password'),
        ]);

        $property = Property::create([
            'name' => 'Flow Plaza',
            'address' => '123 Flow St',
            'type' => 'residential',
            'status' => 'active',
        ]);

        $property->units()->create([
            'name' => 'Unit 1',
            'rent_amount' => 1000,
            'status' => 'vacant',
        ]);

        Renter::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@test.com',
            'phone' => '555-0199',
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        tenancy()->end();

        // 3. Test Login
        $response = $this->postJson('http://' . $domain . '/api/login', [
            'email' => 'flow@test.com',
            'password' => 'password',
            'device_name' => 'TestDevice',
        ]);

        $response->assertStatus(200);
        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // 4. Test Endpoints
        $headers = ['Authorization' => 'Bearer ' . $token];

        // Properties
        $this->withHeaders($headers)->getJson('http://' . $domain . '/api/properties')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Flow Plaza']);

        // Units
        $this->withHeaders($headers)->getJson('http://' . $domain . '/api/units')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Unit 1']);

        // Renters
        $this->withHeaders($headers)->getJson('http://' . $domain . '/api/renters')
            ->assertStatus(200)
            ->assertJsonFragment(['first_name' => 'Jane']);

        // Leases (Empty list check)
        $this->withHeaders($headers)->getJson('http://' . $domain . '/api/leases')
            ->assertStatus(200);

        // 5. Cleanup
        $tenant->delete();
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }
}
