<?php

namespace Database\Factories;

use App\Enums\LeaseStatus;
use App\Models\Renter;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'renter_id' => Renter::factory(),
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'rent_amount' => 100000,
            'deposit_amount' => 200000,
            'status' => LeaseStatus::Active,
            'documents' => [],
        ];
    }
}
