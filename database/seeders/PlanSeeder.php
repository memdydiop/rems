<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Optional: Truncate to ensure clean slate? Or just delete old codes?
        // Let's delete old codes explicitly to cleanup.
        Plan::whereIn('paystack_code', ['PLN_basic', 'PLN_monthly', 'PLN_standard_yearly', 'PLN_premium', 'PLN_premium_yearly'])->delete();

        // OR better yet, just truncate if we are sure? No, existing subscriptions might link to IDs. 
        // We should probably just soft delete or update them. 
        // But if they are 'updateOrCreate' and we changed keys, we have duplicates.

        // Let's rely on name updates or just delete everything for this dev environment?
        // User is in dev.
        // Plan::truncate(); // Risky if foreign keys.

        // Plan 1: Starter

        $starterFeatures = [
            'Propriétés max' => 20,
            'Utilisateurs max' => 1,
            'Suivi manuel des loyers' => true,
            'Portail locataire basique' => true,
            'Demandes de maintenance' => true,
        ];

        Plan::updateOrCreate(['paystack_code' => 'PLN_starter'], [
            'name' => 'Starter',
            'amount' => 1900000, // 19 000 XOF (approx $29)
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Pour les nouveaux bailleurs (1-20 unités).',
            'features' => $starterFeatures,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_starter_yearly'], [
            'name' => 'Starter (Annuel)',
            'amount' => 19000000, // 190 000 XOF
            'currency' => 'XOF',
            'interval' => 'annually',
            'description' => 'Pour les nouveaux bailleurs (1-20 unités).',
            'features' => $starterFeatures,
        ]);

        // Growth Plan
        $growthFeatures = array_merge($starterFeatures, [
            'Propriétés max' => -1, // Unlimited
            'Utilisateurs max' => 3,
            'Paiements en ligne' => true,
            'Gestion des dépenses' => true,
            'Sélection des locataires' => true,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_growth'], [
            'name' => 'Croissance',
            'amount' => 6500000, // 65 000 XOF (approx $99)
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Pour les portefeuilles en croissance.',
            'features' => $growthFeatures,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_growth_yearly'], [
            'name' => 'Croissance (Annuel)',
            'amount' => 65000000, // 650 000 XOF
            'currency' => 'XOF',
            'interval' => 'annually',
            'description' => 'Pour les portefeuilles en croissance.',
            'features' => $growthFeatures,
        ]);

        // Business Plan
        $businessFeatures = array_merge($growthFeatures, [
            'Utilisateurs max' => -1, // Unlimited
            'Portails propriétaires' => true,
            'Rôles multi-utilisateurs' => true,
            'Support prioritaire' => true,
            'Accès API' => true,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_business'], [
            'name' => 'Entreprise',
            'amount' => 16500000, // 165 000 XOF (approx $249)
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Pour les agences et les équipes.',
            'features' => $businessFeatures,
        ]);

        Plan::updateOrCreate(['paystack_code' => 'PLN_business_yearly'], [
            'name' => 'Entreprise (Annuel)',
            'amount' => 165000000, // 1 650 000 XOF
            'currency' => 'XOF',
            'interval' => 'annually',
            'description' => 'Pour les agences et les équipes.',
            'features' => $businessFeatures,
        ]);

        // Developer Plan
        Plan::updateOrCreate(['paystack_code' => 'PLN_dev'], [
            'name' => 'Developer',
            'amount' => 0,
            'currency' => 'XOF',
            'interval' => 'monthly',
            'description' => 'Accès au développement interne',
            'features' => [
                'max_properties' => -1,
                'max_users' => -1,
                'api_access' => true,
                'owner_portals' => true,
                'everything' => true
            ],
        ]);
    }
}
