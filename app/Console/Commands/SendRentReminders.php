<?php

namespace App\Console\Commands;

use App\Enums\LeaseStatus;
use App\Models\Lease;
use App\Models\Tenant;
use App\Notifications\RentReminderNotification;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class SendRentReminders extends Command
{
    protected $signature = 'pms:send-rent-reminders {--days=5 : Days before due date to send reminder}';
    protected $description = 'Send rent payment reminder notifications to clients';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $totalSent = 0;

        // Iterate over all tenants
        Tenant::all()->each(function ($tenant) use ($days, &$totalSent) {
            Tenancy::initialize($tenant);

            try {
                $leases = Lease::where('status', LeaseStatus::Active)
                    ->with(['client', 'unit.property'])
                    ->get();

                foreach ($leases as $lease) {
                    $client = $lease->client;

                    if (!$client || !$client->email) {
                        continue;
                    }

                    // Calculate next rent due date (assuming 1st of month)
                    $rentDueDay = 1;
                    $nextDueDate = now()->startOfMonth()->addDays($rentDueDay - 1);

                    if ($nextDueDate->isPast()) {
                        $nextDueDate = $nextDueDate->addMonth();
                    }

                    // Send reminder if due date is in X days
                    if ($nextDueDate->diffInDays(now()) === $days) {
                        $client->notify(new RentReminderNotification($lease, $days));
                        $totalSent++;
                        $this->info("Sent reminder to {$client->email} for {$lease->unit?->name}");
                    }
                }
            } finally {
                Tenancy::end();
            }
        });

        $this->info("Sent {$totalSent} rent reminder notifications.");
        return Command::SUCCESS;
    }
}
