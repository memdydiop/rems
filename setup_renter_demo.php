// 1. Get or Create Tenant
$tenant = \App\Models\Tenant::first();
if (!$tenant) {
    echo "Creating a new tenant 'demo'...\n";
    $tenant = \App\Models\Tenant::create(['id' => 'demo', 'company' => 'Demo Housing']);
    $tenant->domains()->create(['domain' => 'demo.localhost']);
}
echo "Using tenant: " . $tenant->id . "\n";

// 2. Initialize Tenancy
tenancy()->initialize($tenant);

// 3. Create User
$email = 'renter@example.com';
$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "Creating user {$email}...\n";
    $user = \App\Models\User::factory()->create([
        'name' => 'John Renter',
        'email' => $email,
        'password' => bcrypt('password'),
    ]);
} else {
    echo "User {$email} already exists.\n";
}

// 4. Create Renter Profile
$renter = \App\Models\Renter::where('user_id', $user->id)->first();
if (!$renter) {
    echo "Creating renter profile...\n";
    $renter = \App\Models\Renter::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Renter',
        'email' => $email,
        'phone' => '0102030405',
        'status' => 'active',
    ]);
}

// 5. Create Property & Unit
$property = \App\Models\Property::first();
if (!$property) {
    echo "Creating property...\n";
    $property = \App\Models\Property::factory()->create([
        'name' => 'Résidence Les Palmiers',
        'type' => \App\Enums\PropertyType::Residential,
    ]);
}

$unit = \App\Models\Unit::where('property_id', $property->id)->first();
if (!$unit) {
    echo "Creating unit...\n";
    $unit = \App\Models\Unit::create([
        'property_id' => $property->id,
        'name' => 'Apt 101',
        'rent_amount' => 150000,
        'status' => \App\Enums\UnitStatus::Occupied,
    ]);
}

// 6. Create Active Lease
$lease = \App\Models\Lease::where('unit_id', $unit->id)->where('status', \App\Enums\LeaseStatus::Active)->first();
if (!$lease) {
    echo "Creating active lease...\n";
    $lease = \App\Models\Lease::create([
        'unit_id' => $unit->id,
        'renter_id' => $renter->id,
        'start_date' => now()->subMonths(3),
        'end_date' => now()->addMonths(9),
        'rent_amount' => 150000,
        'deposit_amount' => 300000,
        'status' => \App\Enums\LeaseStatus::Active,
        'documents' => [],
    ]);
}

echo "\n--------------------------------------------------\n";
echo "✅ SETUP COMPLETE\n";
echo "--------------------------------------------------\n";
echo "Tenant URL : http://" . $tenant->domains->first()->domain . ":8000/renter\n";
echo "Login      : $email\n";
echo "Password   : password\n";
echo "--------------------------------------------------\n";
