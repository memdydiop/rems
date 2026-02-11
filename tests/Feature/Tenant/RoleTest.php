<?php

namespace Tests\Feature\Tenant;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TenantTestCase;

class RoleTest extends TenantTestCase
{
    // setUp is handled by parent, but we need to run permissions setup
    protected function setUp(): void
    {
        parent::setUp();

        // Additional Role Test Setup
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Permission::create(['name' => 'delete projects', 'guard_name' => 'web']);

        $admin = \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo('delete projects');

        \Spatie\Permission\Models\Role::create(['name' => 'member', 'guard_name' => 'web']);
    }

    public function test_member_cannot_delete_project()
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        $project = new Project();
        $project->setConnection('tenant');
        $project->fill(['name' => 'Test Project', 'status' => 'active']);
        $project->save();

        $this->assertTrue($member->cannot('delete', $project));
    }

    public function test_admin_can_delete_project()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $project = new Project();
        $project->setConnection('tenant');
        $project->fill(['name' => 'Test Project', 'status' => 'active']);
        $project->save();

        $this->assertTrue($admin->can('delete', $project));
    }
}
