<?php

namespace App\Traits;

trait HasPlanLimits
{
    /**
     * Get the limit for a specific feature from the active plan.
     * Returns null if unlimited (or if logically it should be).
     * Returns 0 if not allowed.
     */
    public function getFeatureLimit(string $feature): ?int
    {
        $subscription = $this->subscription;

        if (!$subscription || !$subscription->plan) {
            return 0; // No plan = no resources
        }

        $features = $subscription->plan->features ?? [];

        // Check if key exists
        if (!array_key_exists($feature, $features)) {
            return 0; // Default to 0 if not specified? Or unlimited? 
            // Better to default to 0 for strictness.
        }

        $limit = $features[$feature];

        if ($limit === -1 || $limit === 'unlimited') {
            return null; // Unlimited
        }

        return (int) $limit;
    }

    /**
     * Check if the tenant can create a specific resource.
     * 
     * @param string $feature The feature key (e.g., 'max_users')
     * @param int $currentUsage The current count of the resource
     * @return bool
     */
    public function canCreate(string $feature, int $currentUsage): bool
    {
        $limit = $this->getFeatureLimit($feature);

        if ($limit === null) {
            return true; // Unlimited
        }

        return $currentUsage < $limit;
    }
}
