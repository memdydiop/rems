<?php

namespace Tests\Feature\Tenant;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run tenant migrations (including activity_log)
        $this->artisan('migrate', ['--path' => 'database/migrations/tenant']);
    }

    public function test_actions_are_logged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Perform action: Create Project
        $project = Project::create([
            'name' => 'Logged Project',
            'status' => 'active',
        ]);

        // Assert Activity Log database has entry
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'description' => 'created',
            'causer_id' => $user->id,
        ]);
    }

    public function test_activity_log_page_is_accessible(): void
    {
        $user = User::factory()->create();


        \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole('admin');

        Livewire::actingAs($user)
            ->test('pages.tenant.settings.activity')
            ->assertStatus(200)
            ->assertSee('Recent Activity');
    }
}
