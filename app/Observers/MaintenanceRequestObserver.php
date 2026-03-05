<?php

namespace App\Observers;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
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
        $property = $maintenanceRequest->property;

        // Si le ticket est résolu ou annulé, vérifier s'il reste des tickets critiques ouverts
        if (in_array($maintenanceRequest->status, [MaintenanceStatus::Resolved, MaintenanceStatus::Cancelled])) {
            $hasOpenCriticalTickets = MaintenanceRequest::where('property_id', $property->id)
                ->where('id', '!=', $maintenanceRequest->id)
                ->where('category', \App\Enums\MaintenanceCategory::Property) // Uniquement les maintenances au niveau propriété
                ->whereIn('priority', [MaintenancePriority::High, MaintenancePriority::Urgent])
                ->whereNotIn('status', [MaintenanceStatus::Resolved, MaintenanceStatus::Cancelled])
                ->exists();

            if (!$hasOpenCriticalTickets && $property->status === PropertyStatus::Maintenance) {
                $property->update(['status' => PropertyStatus::Active]);
            }

            return;
        }

        // Si nouvelle demande haute/urgente au NIVEAU PROPRIÉTÉ
        if ($maintenanceRequest->category === \App\Enums\MaintenanceCategory::Property && ($maintenanceRequest->priority === MaintenancePriority::High || $maintenanceRequest->priority === MaintenancePriority::Urgent)) {
            $property->update(['status' => PropertyStatus::Maintenance]);
        }
    }
}
