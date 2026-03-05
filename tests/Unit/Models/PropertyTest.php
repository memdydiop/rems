<?php

namespace Tests\Unit\Models;

use App\Models\Property;
use App\Models\Unit;
use App\Enums\PropertyType;
use App\Enums\PropertyStatus;
use Tests\TenantTestCase;

class PropertyTest extends TenantTestCase
{
    public function test_property_can_be_created()
    {
        $property = Property::factory()->create([
            'name' => 'Sunset Villa',
            'type' => PropertyType::ResidentialBuilding,
            'status' => PropertyStatus::Active,
        ]);

        $this->assertDatabaseHas('properties', [
            'name' => 'Sunset Villa',
            'type' => PropertyType::ResidentialBuilding->value,
        ]);
    }

    public function test_property_has_units()
    {
        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        $this->assertTrue($property->units->contains($unit));
        $this->assertInstanceOf(Unit::class, $property->units->first());
    }
}
