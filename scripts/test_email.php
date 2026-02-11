<?php

use App\Models\User;
use App\Notifications\TenantResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Config;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Email Sending...\n";
echo "Mailer: " . config('mail.default') . "\n";
echo "Host: " . config('mail.mailers.smtp.host') . "\n";
echo "Port: " . config('mail.mailers.smtp.port') . "\n";

// Force Sync driver to test immediate failure/success
Config::set('queue.default', 'sync');

try {
    $user = new User();
    $user->email = 'test@example.com';
    $user->name = 'Test User';

    $notification = new TenantResetPassword('http://test-url.com');

    // We use the 'mail' channel directly
    $user->notify($notification);

    echo "\n[SUCCESS] Email sent successfully to Mailtrap (check inbox)!\n";
} catch (\Exception $e) {
    echo "\n[ERROR] Failed to send email:\n";
    echo $e->getMessage() . "\n";
}
