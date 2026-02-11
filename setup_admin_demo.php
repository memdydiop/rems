// 1. Setup Tenant Context
$tenant = \App\Models\Tenant::find('dev');
if (!$tenant) {
echo "Tenant 'dev' not found. Please run setup_renter_demo.php first.\n";
exit;
}
tenancy()->initialize($tenant);

// 2. Create Admin User
$email = 'admin@example.com';
$password = 'password';

$user = \App\Models\User::where('email', $email)->first();

if (!$user) {
echo "Creating admin user...\n";
$user = \App\Models\User::factory()->create([
'name' => 'Admin User',
'email' => $email,
'password' => bcrypt($password),
]);
} else {
echo "Admin user already exists.\n";
$user->update(['password' => bcrypt($password)]);
}

echo "\n--------------------------------------------------\n";
echo "✅ ADMIN SETUP COMPLETE\n";
echo "--------------------------------------------------\n";
echo "URL : http://dev.localhost:8000/owners\n";
echo "Login : $email\n";
echo "Password : $password\n";
echo "--------------------------------------------------\n";