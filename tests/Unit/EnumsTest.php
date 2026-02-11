<?php

namespace Tests\Unit;

use App\Enums\UnitStatus;
use App\Enums\LeaseStatus;
use App\Enums\PaymentStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenancePriority;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_unit_status_enum_labels_and_colors()
    {
        $this->assertEquals('vacant', UnitStatus::Vacant->value);
        $this->assertEquals('available', UnitStatus::Available->value);
        $this->assertEquals('Disponible', UnitStatus::Vacant->label());
        $this->assertEquals('Disponible', UnitStatus::Available->label());
        $this->assertEquals('zinc', UnitStatus::Vacant->color());

        $this->assertEquals('occupied', UnitStatus::Occupied->value);
        $this->assertEquals('Occupé', UnitStatus::Occupied->label());
        $this->assertEquals('green', UnitStatus::Occupied->color());
    }

    public function test_lease_status_enum()
    {
        $this->assertEquals('active', LeaseStatus::Active->value);
        $this->assertEquals('Actif', LeaseStatus::Active->label());
        $this->assertEquals('green', LeaseStatus::Active->color());

        $this->assertEquals('expired', LeaseStatus::Expired->value);
        $this->assertEquals('red', LeaseStatus::Expired->color());
    }

    public function test_payment_status_enum()
    {
        $this->assertEquals('completed', PaymentStatus::Completed->value);
        $this->assertEquals('Complété', PaymentStatus::Completed->label());
        $this->assertEquals('green', PaymentStatus::Completed->color());
    }

    public function test_maintenance_status_enum()
    {
        $this->assertEquals('pending', MaintenanceStatus::Pending->value);
        $this->assertEquals('En attente', MaintenanceStatus::Pending->label());
        $this->assertEquals('amber', MaintenanceStatus::Pending->color());
    }

    public function test_maintenance_priority_enum()
    {
        $this->assertEquals('urgent', MaintenancePriority::Urgent->value);
        $this->assertEquals('Urgente', MaintenancePriority::Urgent->label());
        $this->assertEquals('red', MaintenancePriority::Urgent->color());
    }
}
