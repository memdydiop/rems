<?php

namespace Tests\Unit\Models;

use App\Models\Lease;
use App\Models\RentPayment;
use App\Enums\PaymentStatus;
use Tests\TenantTestCase;

class RentPaymentTest extends TenantTestCase
{
    public function test_payment_can_be_created()
    {
        $payment = RentPayment::factory()->create([
            'amount' => 150000,
            'status' => PaymentStatus::Completed,
        ]);

        $this->assertDatabaseHas('rent_payments', [
            'id' => $payment->id,
            'amount' => 150000,
            'status' => PaymentStatus::Completed->value,
        ]);
    }

    public function test_payment_belongs_to_lease()
    {
        $lease = Lease::factory()->create();
        $payment = RentPayment::factory()->create(['lease_id' => $lease->id]);

        $this->assertEquals($lease->id, $payment->lease->id);
    }
}
