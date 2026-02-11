<?php

namespace App\Console\Commands;

use App\Enums\LeaseStatus;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LeaseExpiringNotification;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class SendLeaseAlerts extends Command
{
    protected $signature = 'pms:send-lease-alerts';
    protected $description = 'Send notifications for leases expiring in 30 days and 7 days';

    public function handle(): int
    {
        $totalSent = 0;

        Tenant::all()->each(function ($tenant) use (&$totalSent) {
            Tenancy::initialize($tenant);

            try {
                // Get leases expiring in 30 days
                $this->sendAlertsForDays(30, $totalSent);

                // Get leases expiring in 7 days
                $this->sendAlertsForDays(7, $totalSent);

            } finally {
                Tenancy::end();
            }
        });

        $this->info("Sent {$totalSent} lease expiring notifications.");
        return Command::SUCCESS;
    }

    private function sendAlertsForDays(int $days, int &$totalSent): void
    {
        $targetDate = now()->addDays($days)->startOfDay();

        $leases = Lease::where('status', LeaseStatus::Active)
            ->whereDate('end_date', $targetDate)
            ->with(['renter', 'unit.property'])
            ->get();

        foreach ($leases as $lease) {
            // Notify all users (property managers) in the tenant
            $users = User::all();

            foreach ($users as $user) {
                $user->notify(new LeaseExpiringNotification($lease, $days));
                $totalSent++;
                $this->info("Sent {$days}-day alert to {$user->email} for lease {$lease->id}");
            }
        }
    }
}
