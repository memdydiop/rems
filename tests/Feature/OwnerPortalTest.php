<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Owner;
use App\Models\Property;
use Tests\TenantTestCase;

class OwnerPortalTest extends TenantTestCase
{
    public function test_non_owner_cannot_access_portal()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('owner.dashboard'))
            ->assertForbidden();
    }

    public function test_owner_can_access_dashboard()
    {
        $user = User::factory()->create();
        $owner = Owner::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('Bonjour, ' . $owner->first_name);
    }

    public function test_owner_sees_own_properties_stats()
    {
        $user = User::factory()->create();
        $owner = Owner::factory()->create(['user_id' => $user->id]);

        $property = Property::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'My Villa'
        ]);

        $this->actingAs($user)
            ->get(route('owner.dashboard'))
            ->assertSee('My Villa')
            ->assertSee('Biens en gestion');
    }

    public function test_owner_can_download_report()
    {
        $user = User::factory()->create();
        $owner = Owner::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('owner.report', ['year' => now()->year, 'month' => now()->month]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
