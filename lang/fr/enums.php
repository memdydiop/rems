<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Traductions des Enums
    |--------------------------------------------------------------------------
    */

    // LeaseStatus
    'lease_status' => [
        'active' => 'Actif',
        'expired' => 'Expiré',
        'terminated' => 'Résilié',
        'pending' => 'En attente',
    ],

    // PropertyStatus
    'property_status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    // PropertyType
    'property_type' => [
        'apartment_building' => 'Immeuble d\'appartements',
        'house' => 'Maison',
        'villa' => 'Villa',
        'office_building' => 'Immeuble de bureaux',
        'commercial' => 'Local commercial',
        'land' => 'Terrain',
        'studio' => 'Studio',
        'duplex' => 'Duplex',
        'warehouse' => 'Entrepôt',
        'mixed_use' => 'Usage mixte',
    ],

    // UnitStatus
    'unit_status' => [
        'vacant' => 'Vacant',
        'occupied' => 'Occupé',
        'maintenance' => 'En maintenance',
    ],

    // UnitType
    'unit_type' => [
        'apartment' => 'Appartement',
        'studio' => 'Studio',
        'room' => 'Chambre',
        'shop' => 'Boutique',
        'office' => 'Bureau',
        'garage' => 'Garage',
        'storage' => 'Entrepôt',
    ],

    // RenterStatus
    'renter_status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    // OwnerStatus
    'owner_status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    // MaintenanceStatus
    'maintenance_status' => [
        'open' => 'Ouvert',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
        'cancelled' => 'Annulé',
    ],

    // MaintenancePriority
    'maintenance_priority' => [
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'urgent' => 'Urgente',
    ],

    // PaymentStatus
    'payment_status' => [
        'pending' => 'En attente',
        'completed' => 'Complété',
        'failed' => 'Échoué',
        'refunded' => 'Remboursé',
    ],

];
