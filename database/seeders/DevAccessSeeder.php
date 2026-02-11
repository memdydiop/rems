<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Facades\Tenancy;

class DevAccessSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Find or Create the Developer Plan
        $devPlan = Plan::where('paystack_code', 'PLN_dev')->first();
        if (!$devPlan) {
            $this->command->error("Developer Plan not found! Run PlanSeeder first.");
            return;
        }

        // 2. Find or Create the Tenant
        // checks if tenant exists properly
        $tenant = Tenant::find('dev');

        if (!$tenant) {
            $this->command->info("Creating 'dev' tenant...");

            try {
                $tenant = Tenant::create([
                    'id' => 'dev',
                    'company' => 'Developer Corp',
                    'tenancy_db_name' => 'pms_tenant_dev', // Optional: fixed DB name for easier debugging
                ]);
            } catch (\Stancl\Tenancy\Exceptions\TenantDatabaseAlreadyExistsException $e) {
                $this->command->warn("Tenant database 'pms_tenant_dev' exists but tenant record is missing. Dropping database to recreate...");

                // Manually delete the database using a temporary tenant instance
                $tempTenant = new Tenant(['id' => 'dev', 'tenancy_db_name' => 'pms_tenant_dev']);
                app(\Stancl\Tenancy\Database\DatabaseManager::class)->deleteDatabase($tempTenant);

                // Retry creation
                $tenant = Tenant::create([
                    'id' => 'dev',
                    'company' => 'Developer Corp',
                    'tenancy_db_name' => 'pms_tenant_dev',
                ]);
            }

            $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
            $tenant->domains()->create(['domain' => 'dev.' . $centralDomain]);
        } else {
            $this->command->info("'dev' tenant already exists.");
        }

        // 3. Ensure Subscription (Create or Update)
        $this->command->info("Ensuring subscription to Developer plan...");
        $tenant->subscription()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $devPlan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => null, // Lifetime
            ]
        );

        // 4. Create User in Tenant Context
        $this->command->info("Initializing tenancy to create user...");
        Tenancy::initialize($tenant);

        $user = User::firstOrCreate(
            ['email' => 'dev@pms.test'],
            [
                'name' => 'Developer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Role
        if (\Spatie\Permission\Models\Role::where('name', 'Admin')->exists()) {
            if (!$user->hasRole('Admin')) {
                $user->assignRole('Admin');
                $this->command->info("Assigned 'Admin' role to user.");
            }
        } else {
            $this->command->warn("Role 'Admin' not found. RolesSeeder might not have run.");
        }

        $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
        $this->command->info("Developer Access Ready!");
        $this->command->info("URL: http://dev." . $centralDomain);
        $this->command->info("User: dev@pms.test");
        $this->command->info("Pass: password");

        Tenancy::end();
    }
}
