<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_manage_roles_via_modal()
    {
        // Override tenant connection template for testing
        config([
            'database.connections.tenant_template' => [
                'driver' => 'sqlite',
                'foreign_key_constraints' => true,
            ]
        ]);

        // Setup Tenant
        $tenantId = 'test-tenant-' . Str::random(4);
        $dbName = 'test_tenant_' . $tenantId . '.sqlite';
        $dbPath = database_path($dbName);
        // touch($dbPath); // Removed: let tenancy handle creation or ensure check doesn't fail

        $tenant = Tenant::create([
            'id' => $tenantId,
            'tenancy_db_name' => $dbName,
        ]);
        $tenant->domains()->create(['domain' => $tenantId . '.' . config('tenancy.suffix')]);

        tenancy()->initialize($tenant);

        // Setup User & Permissions
        $user = User::factory()->create();
        Permission::create(['name' => 'edit posts', 'guard_name' => 'web']);

        $this->actingAs($user);

        // 1. Create Role
        Livewire::test('pages::tenant.settings.roles.index')
            ->call('create')
            ->set('name', 'Editor')
            ->set('description', 'Edits content')
            ->set('selectedPermissions', ['edit posts'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Editor',
            'guard_name' => 'web',
            'description' => 'Edits content'
        ]);
        $role = Role::where('name', 'Editor')->first();
        $this->assertTrue($role->hasPermissionTo('edit posts'));

        // 2. Edit Role
        Livewire::test('pages::tenant.settings.roles.index')
            ->call('edit', $role->id)
            ->set('name', 'Senior Editor')
            ->set('description', 'Edits everything')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', ['name' => 'Senior Editor', 'description' => 'Edits everything']);

        // 3. Delete Role
        Livewire::test('pages::tenant.settings.roles.index')
            ->call('delete', $role->id);

        $this->assertDatabaseMissing('roles', ['name' => 'Senior Editor']);

        // Cleanup
        $tenant->delete();
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }
}
