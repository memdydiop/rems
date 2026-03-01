<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enum Translations
    |--------------------------------------------------------------------------
    */

    // LeaseStatus
    'lease_status' => [
        'active' => 'Active',
        'expired' => 'Expired',
        'terminated' => 'Terminated',
        'pending' => 'Pending',
    ],

    // PropertyStatus
    'property_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // PropertyType
    'property_type' => [
        'apartment_building' => 'Apartment Building',
        'house' => 'House',
        'villa' => 'Villa',
        'office_building' => 'Office Building',
        'commercial' => 'Commercial Space',
        'land' => 'Land',
        'studio' => 'Studio',
        'duplex' => 'Duplex',
        'warehouse' => 'Warehouse',
        'mixed_use' => 'Mixed Use',
    ],

    // UnitStatus
    'unit_status' => [
        'vacant' => 'Vacant',
        'occupied' => 'Occupied',
        'maintenance' => 'Under Maintenance',
    ],

    // UnitType
    'unit_type' => [
        'apartment' => 'Apartment',
        'studio' => 'Studio',
        'room' => 'Room',
        'shop' => 'Shop',
        'office' => 'Office',
        'garage' => 'Garage',
        'storage' => 'Storage',
    ],

    // RenterStatus
    'renter_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // OwnerStatus
    'owner_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // MaintenanceStatus
    'maintenance_status' => [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'cancelled' => 'Cancelled',
    ],

    // MaintenancePriority
    'maintenance_priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    // PaymentStatus
    'payment_status' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],

];
