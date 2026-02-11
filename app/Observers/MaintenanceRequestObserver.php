<?php

namespace App\Observers;

use App\Enums\MaintenancePriority;
use App\Enums\PropertyStatus;
use App\Models\MaintenanceRequest;

class MaintenanceRequestObserver
{
    /**
     * Handle the MaintenanceRequest "created" event.
     */
    public function created(MaintenanceRequest $maintenanceRequest): void
    {
        $this->checkPriorityAndUpdateProperty($maintenanceRequest);
    }

    /**
     * Handle the MaintenanceRequest "updated" event.
     */
    public function updated(MaintenanceRequest $maintenanceRequest): void
    {
        $this->checkPriorityAndUpdateProperty($maintenanceRequest);
    }

    /**
     * Check priority and update property status if necessary.
     */
    protected function checkPriorityAndUpdateProperty(MaintenanceRequest $maintenanceRequest): void
    {
        if ($maintenanceRequest->priority === MaintenancePriority::High || $maintenanceRequest->priority === MaintenancePriority::Urgent) {
            $maintenanceRequest->property->update([
                'status' => PropertyStatus::Maintenance,
            ]);
        }
    }
}
