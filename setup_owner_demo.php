<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Owner;
use App\Models\Property;

// 1. Setup Tenant Context
$tenant = \App\Models\Tenant::find('dev');
if (!$tenant) {
    echo "Tenant 'dev' not found. Please run setup_renter_demo.php first.\n";
    exit;
}
tenancy()->initialize($tenant);

// 2. Create a few Owners
echo "Creating owners...\n";
$owners = \App\Models\Owner::factory()->count(3)->create();

foreach ($owners as $owner) {
    echo " - Created: {$owner->first_name} {$owner->last_name} ({$owner->email})\n";
}

// 3. Link one to a Property
$property = \App\Models\Property::first();
if ($property) {
    $owner = $owners->first();
    $property->update(['owner_id' => $owner->id]);
    echo "Linked property '{$property->name}' to owner {$owner->first_name}.\n";
}

echo "\n✅ Test data created!\n";
echo "Go to: http://dev.localhost:8000/owners\n";
