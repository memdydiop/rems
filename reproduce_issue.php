<?php

use App\Models\Tenant;
use Illuminate\Support\Str;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$name = 'Repro Tenant '.Str::random(5);
$domainStr = 'repro-'.Str::random(5);
$tenantId = Str::slug($name);

echo "Creating tenant: $tenantId\n";
echo "Domain: $domainStr\n";

try {
    $tenant = Tenant::create(['id' => $tenantId]);
    echo 'Tenant created. ID: '.$tenant->id."\n";

    $domain = $tenant->domains()->create(['domain' => $domainStr]);
    echo 'Domain created. ID: '.$domain->id.' Domain: '.$domain->domain.' TenantID: '.$domain->tenant_id."\n";

} catch (\Throwable $e) {
    echo 'Error: '.$e->getMessage()."\n";
    if (isset($tenant)) {
        $tenant->delete();
        echo "Tenant deleted due to error.\n";
    }
    exit(1);
}

// Now verify relation retrieval
$retrievedTenant = Tenant::with('domains')->find($tenantId);
echo 'Retrieved Tenant domains count: '.$retrievedTenant->domains->count()."\n";
foreach ($retrievedTenant->domains as $d) {
    echo ' - '.$d->domain."\n";
}

// Cleanup
$retrievedTenant->delete();
echo "Cleanup done.\n";
