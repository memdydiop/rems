<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Lease;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_id' => Lease::factory(),
            'amount' => 100000,
            'paid_at' => now(),
            'method' => 'cash',
            'status' => PaymentStatus::Completed,
            'notes' => $this->faker->sentence(),
        ];
    }
}
