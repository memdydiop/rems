<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up old plans
        Plan::whereIn('paystack_code', ['PLN_basic', 'PLN_monthly', 'PLN_standard_yearly', 'PLN_premium', 'PLN_premium_yearly'])->delete();

        // ─── Starter ───────────────────────────────────────────────
        $starterFeatures = [
            'max_properties' => 20,
            'max_users' => 1,
            'rent_tracking' => true,
            'renter_portal' => true,
            'maintenance_requests' => true,
        ];

        Plan::updateOrCreate(['paystack_code' => 'PLN_starter'], [
            'name' => 'Starter',
            'amount' => 1900000, // 19 000 XOF/mois
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Pour les nouveaux bailleurs (1-20 unités).',
            'features' => $starterFeatures,
            'is_public' => true,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_starter_yearly'], [
            'name' => 'Starter (Annuel)',
            'amount' => 17100000, // 171 000 XOF/an (= 10 mois, -2 mois offerts)
            'currency' => 'XOF',
            'interval' => 'annually',
            'description' => 'Pour les nouveaux bailleurs (1-20 unités).',
            'features' => $starterFeatures,
            'is_public' => true,
        ]);

        // ─── Croissance ────────────────────────────────────────────
        $growthFeatures = array_merge($starterFeatures, [
            'max_properties' => -1, // Illimité
            'max_users' => 3,
            'online_payments' => true,
            'expense_management' => true,
            'tenant_screening' => true,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_growth'], [
            'name' => 'Croissance',
            'amount' => 6500000, // 65 000 XOF/mois
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Pour les portefeuilles en croissance.',
            'features' => $growthFeatures,
            'is_public' => true,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_growth_yearly'], [
            'name' => 'Croissance (Annuel)',
            'amount' => 65000000, // 650 000 XOF/an (= 10 mois)
            'currency' => 'XOF',
            'interval' => 'annually',
            'description' => 'Pour les portefeuilles en croissance.',
            'features' => $growthFeatures,
            'is_public' => true,
        ]);

        // ─── Entreprise ────────────────────────────────────────────
        $businessFeatures = array_merge($growthFeatures, [
            'max_users' => -1, // Illimité
            'owner_portals' => true,
            'multi_user_roles' => true,
            'priority_support' => true,
            'api_access' => true,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_business'], [
            'name' => 'Entreprise',
            'amount' => 16500000, // 165 000 XOF/mois
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Pour les agences et les équipes.',
            'features' => $businessFeatures,
            'is_public' => true,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_business_yearly'], [
            'name' => 'Entreprise (Annuel)',
            'amount' => 165000000, // 1 650 000 XOF/an (= 10 mois)
            'currency' => 'XOF',
            'interval' => 'annually',
            'description' => 'Pour les agences et les équipes.',
            'features' => $businessFeatures,
            'is_public' => true,
        ]);

        // ─── Developer (interne, non public) ───────────────────────
        Plan::updateOrCreate(['paystack_code' => 'PLN_dev'], [
            'name' => 'Developer',
            'amount' => 0,
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Accès au développement interne',
            'features' => [
                'max_properties' => -1,
                'max_users' => -1,
                'rent_tracking' => true,
                'renter_portal' => true,
                'maintenance_requests' => true,
                'online_payments' => true,
                'expense_management' => true,
                'tenant_screening' => true,
                'owner_portals' => true,
                'multi_user_roles' => true,
                'priority_support' => true,
                'api_access' => true,
            ],
            'is_public' => false,
        ]);
    }
}
