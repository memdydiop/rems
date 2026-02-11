<?php

namespace Tests\Feature\Central;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_manage_roles_via_modal()
    {
        $user = User::factory()->create();

        // Grant Ghost access via Gate::before in AppServiceProvider
        // Or simply create a role/permission if we haven't set up the ghost user yet
        // For this test, we rely on the Gate::before check? Or just assign role.
        // Let's create a Ghost role and assign it to be safe and consistent.
        $ghostRole = Role::create(['name' => 'Ghost', 'guard_name' => 'web']);
        $user->assignRole($ghostRole);

        Permission::create(['name' => 'manage roles', 'guard_name' => 'web']);

        $this->actingAs($user);

        // 1. Visit Index
        $response = $this->get(route('central.settings.roles.index'));
        $response->assertStatus(200);

        // 2. Create Role via Modal
        Livewire::test('pages::central.settings.roles.index')
            ->call('create') // Open modal
            ->set('name', 'Manager')
            ->set('description', 'Manages stuff')
            ->set('selectedPermissions', ['manage roles'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Manager',
            'guard_name' => 'web',
            'description' => 'Manages stuff'
        ]);

        $newRole = Role::where('name', 'Manager')->first();
        $this->assertTrue($newRole->hasPermissionTo('manage roles'));

        // 3. Edit Role via Modal
        Livewire::test('pages::central.settings.roles.index')
            ->call('edit', $newRole->id) // Load role into modal
            ->set('name', 'Senior Manager')
            ->set('description', 'Manages big stuff')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', ['name' => 'Senior Manager', 'description' => 'Manages big stuff']);
        $this->assertDatabaseMissing('roles', ['name' => 'Manager']);

        // 4. Delete Role
        Livewire::test('pages::central.settings.roles.index')
            ->call('delete', $newRole->id);

        $this->assertDatabaseMissing('roles', ['name' => 'Senior Manager']);
    }
}
