<?php

namespace App\Console\Commands;

use App\Enums\LeaseStatus;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LeaseExpiringNotification;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class SendLeaseExpirationAlerts extends Command
{
    protected $signature = 'pms:send-lease-expiration-alerts {--days=30 : Days before expiration to send alert}';
    protected $description = 'Send lease expiration alerts to property managers across all tenants';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $totalSent = 0;

        Tenant::all()->each(function ($tenant) use ($days, &$totalSent) {
            Tenancy::initialize($tenant);

            try {
                $targetDate = now()->addDays($days)->startOfDay();

                $leases = Lease::where('status', LeaseStatus::Active)
                    ->whereDate('end_date', '<=', $targetDate)
                    ->whereDate('end_date', '>', now())
                    ->with(['client', 'unit.property'])
                    ->get();

                foreach ($leases as $lease) {
                    $daysUntilExpiry = now()->diffInDays($lease->end_date);

                    // Notify all users (property managers) in the tenant
                    $users = User::all();

                    foreach ($users as $user) {
                        $user->notify(new LeaseExpiringNotification($lease, $daysUntilExpiry));
                        $totalSent++;
                        $this->info("Sent {$daysUntilExpiry}-day alert to {$user->email} for lease {$lease->id}");
                    }
                }
            } finally {
                Tenancy::end();
            }
        });

        $this->info("Sent {$totalSent} lease expiration notifications.");
        return Command::SUCCESS;
    }
}
