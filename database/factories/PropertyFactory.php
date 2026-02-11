<?php

namespace Database\Factories;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Building',
            'address' => $this->faker->address(),
            'type' => $this->faker->randomElement(PropertyType::cases()),
            'status' => $this->faker->randomElement(PropertyStatus::cases()),
        ];
    }
}
