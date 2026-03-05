<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTest extends \Tests\TenantTestCase
{
    public function test_tenant_user_can_view_projects_index(): void
    {
        // Mock tenancy environment - actually we can't easily mock full tenancy url routing in feature tests
        // without proper tenancy initialization.
        // However, we can test the Livewire component in isolation.

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::tenant.projects.index')
            ->assertStatus(200)
            ->assertSee('Projets');
    }

    public function test_tenant_user_can_create_project(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::tenant.projects.modals.create') // Volts are often registered with dot syntax
            // Wait, I created it as 'pages.tenant.projects.modals.create'?
            // The file is resources/views/pages/tenant/projects/modals/create.blade.php
            // So Livewire name is 'pages.tenant.projects.modals.create' IF 'pages' is view path.
            // But if namespace 'pages::' is used...
            // Verify what I used in Index.blade.php: <livewire:pages::tenant.projects.modals.create />
            // So assert component name.
            ->set('name', 'New Project')
            ->set('description', 'Test Description')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'description' => 'Test Description',
            'status' => 'active',
        ]);
    }
}
