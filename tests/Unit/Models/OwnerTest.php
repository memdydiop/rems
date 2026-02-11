<?php

namespace Tests\Unit\Models;

use App\Models\Owner;
use App\Models\Property;
use Tests\TenantTestCase;

class OwnerTest extends TenantTestCase
{
    public function test_owner_can_be_created()
    {
        $owner = Owner::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);

        $this->assertDatabaseHas('owners', [
            'id' => $owner->id,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);
    }

    public function test_owner_has_properties()
    {
        $owner = Owner::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($owner->properties->contains($property));
        $this->assertEquals($owner->id, $property->owner->id);
    }
}
