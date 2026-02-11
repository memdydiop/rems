<?php

// 1. Setup Tenant Context
$tenant = \App\Models\Tenant::find('dev');
if (!$tenant) {
    echo "Tenant 'dev' not found.\n";
    exit;
}
tenancy()->initialize($tenant);

// 2. Create Owner User
$email = 'owner@example.com';
$password = 'password';

$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "Creating owner user...\n";
    $user = \App\Models\User::factory()->create([
        'name' => 'John Owner',
        'email' => $email,
        'password' => bcrypt($password),
    ]);
} else {
    echo "Owner user exists. Updating password.\n";
    $user->update(['password' => bcrypt($password)]);
}

// 3. Link to Owner Profile
$owner = \App\Models\Owner::where('email', $email)->first();
if (!$owner) {
    echo "Creating owner profile...\n";
    $owner = \App\Models\Owner::factory()->create([
        'user_id' => $user->id,
        'email' => $email,
        'first_name' => 'John',
        'last_name' => 'Owner',
    ]);
} else {
    echo "Linking existing owner to user.\n";
    $owner->update(['user_id' => $user->id]);
}

// 4. Assign Properties
$properties = \App\Models\Property::all();
if ($properties->count() > 0) {
    echo "Assigning " . $properties->count() . " properties to this owner.\n";
    foreach ($properties as $prop) {
        $prop->update(['owner_id' => $owner->id]);
    }
} else {
    echo "Creating a dummy property for test.\n";
    \App\Models\Property::factory()->create(['owner_id' => $owner->id, 'name' => 'Villa de Test']);
}

echo "\n--------------------------------------------------\n";
echo "✅ OWNER PORTAL SETUP COMPLETE\n";
echo "--------------------------------------------------\n";
echo "URL      : http://dev.localhost:8000/owner\n";
echo "Login    : $email\n";
echo "Password : $password\n";
echo "--------------------------------------------------\n";
