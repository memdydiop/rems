<?php

namespace Tests\Unit\Models;

use App\Models\Lease;
use App\Models\Renter;
use App\Models\Unit;
use App\Models\RentPayment;
use App\Enums\LeaseStatus;
use Tests\TenantTestCase;

class LeaseTest extends TenantTestCase
{
    public function test_lease_can_be_created()
    {
        $lease = Lease::factory()->create([
            'status' => LeaseStatus::Active,
        ]);

        $this->assertDatabaseHas('leases', [
            'id' => $lease->id,
            'status' => LeaseStatus::Active->value,
        ]);
    }

    public function test_lease_belongs_to_unit_and_renter()
    {
        $unit = Unit::factory()->create();
        $renter = Renter::factory()->create();

        $lease = Lease::factory()->create([
            'unit_id' => $unit->id,
            'renter_id' => $renter->id,
        ]);

        $this->assertEquals($unit->id, $lease->unit->id);
        $this->assertEquals($renter->id, $lease->renter->id);
    }

    public function test_lease_has_payments()
    {
        $lease = Lease::factory()->create();

        $payment = RentPayment::factory()->create(['lease_id' => $lease->id]);

        $this->assertTrue($lease->payments->contains($payment));
        $this->assertInstanceOf(RentPayment::class, $lease->payments->first());
    }
}
