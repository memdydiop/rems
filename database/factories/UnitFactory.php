<?php

namespace Database\Factories;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'name' => 'Unit ' . $this->faker->numberBetween(1, 100),
            'type' => $this->faker->randomElement(UnitType::cases()), // Added type
            'rent_amount' => $this->faker->numberBetween(50000, 500000),
            'status' => $this->faker->randomElement(UnitStatus::cases()),
        ];
    }
}
