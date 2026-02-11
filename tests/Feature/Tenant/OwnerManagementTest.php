<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use App\Models\Owner;
use App\Models\Property;
use Tests\TenantTestCase; // Assuming this exists as confirmed before
use Livewire\Livewire;

class OwnerManagementTest extends TenantTestCase
{
    public function test_owners_page_is_accessible()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('tenant.owners.index'))
            ->assertOk()
            ->assertSee('Propriétaires');
    }

    public function test_can_create_owner()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::tenant.owners.modals.create')
            ->set('first_name', 'Alice')
            ->set('last_name', 'Wonderland')
            ->set('email', 'alice@example.com')
            ->set('phone', '123456789')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('owners', [
            'email' => 'alice@example.com',
            'first_name' => 'Alice',
        ]);
    }

    public function test_can_edit_owner()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $owner = Owner::factory()->create();

        Livewire::test('pages::tenant.owners.modals.create')
            ->call('open', 'edit-owner', $owner->id)
            ->assertSet('first_name', $owner->first_name)
            ->set('first_name', 'Updated Name')
            ->call('save');

        $this->assertDatabaseHas('owners', [
            'id' => $owner->id,
            'first_name' => 'Updated Name',
        ]);
    }

    public function test_can_assign_owner_to_property()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $owner = Owner::factory()->create();

        Livewire::test('pages::tenant.properties.modals.create')
            ->set('name', 'New Property')
            ->set('type', 'residential')
            ->set('owner_id', $owner->id)
            ->call('save');

        $this->assertDatabaseHas('properties', [
            'name' => 'New Property',
            'owner_id' => $owner->id,
        ]);
    }
}
