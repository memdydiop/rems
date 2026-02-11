<?php

namespace App\Traits;

trait HasPlanLimits
{
    /**
     * Get the plan model associated with this tenant.
     * Resolves using the string 'plan' column (e.g. 'starter' -> 'Starter').
     */
    public function currentPlan()
    {
        // $this->plan refers to the attribute here usually, or we use getAttribute to be safe
        $planName = $this->getAttribute('plan');

        if (!$planName) {
            return null;
        }

        // Case-insensitive lookup
        return \App\Models\Plan::whereRaw('LOWER(name) = ?', [strtolower($planName)])->first();
    }

    /**
     * Get the limit for a specific feature from the plan.
     * Returns INF if no limit is set or if value is -1.
     */
    public function getFeatureLimit(string $feature): float
    {
        $plan = $this->currentPlan();

        if (!$plan || !$plan->features) {
            // Fallback: If no plan is found, maybe allow infinite? or 0?
            // "Open Bar" logic suggested we might want Default to Infinite if broken,
            // but for "Enforcement" we usually default to 0.
            // Let's default to INF for now to avoid breaking existing users with mismatched plan names,
            // UNLESS user strictly asked for enforcement.
            // User said: "réactive/implémente ces restrictions maintenant".
            // So we should strictly enforce.
            return 0;
        }

        // Parse features array
        $features = $plan->features;

        // Check if feature exists
        if (!array_key_exists($feature, $features)) {
            return 0; // Feature not defined = 0 limit
        }

        $value = $features[$feature];

        // -1 means infinite
        if ($value == -1) {
            return INF;
        }

        return (float) $value;
    }

    /**
     * Check if a boolean feature is enabled.
     */
    public function canUseFeature(string $feature): bool
    {
        $plan = $this->currentPlan();

        if (!$plan || !$plan->features) {
            return false;
        }

        $features = $plan->features;

        if (!array_key_exists($feature, $features)) {
            return false;
        }

        return filter_var($features[$feature], FILTER_VALIDATE_BOOLEAN);
    }

    public function canCreate(string $feature, int $currentCount): bool
    {
        $limit = $this->getFeatureLimit($feature);

        if ($limit === INF) {
            return true;
        }

        return $currentCount < $limit;
    }
}
