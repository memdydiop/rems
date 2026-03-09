<?php

namespace App\Console\Commands;

use App\Enums\LeaseStatus;
use App\Models\Lease;
use App\Models\Tenant;
use App\Notifications\OverduePaymentNotification;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class SendOverdueRelance extends Command
{
    protected $signature = 'pms:send-overdue-relance {--days=5 : Days overdue (5=reminder, 15=warning, 30=urgent)}';
    protected $description = 'Send overdue payment relance notifications to clients';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $level = match (true) {
            $days >= 30 => 'urgent',
            $days >= 15 => 'warning',
            default => 'reminder',
        };

        $totalSent = 0;

        Tenant::all()->each(function ($tenant) use ($days, $level, &$totalSent) {
            Tenancy::initialize($tenant);

            try {
                $leases = Lease::where('status', LeaseStatus::Active)
                    ->with(['client', 'unit.property', 'payments'])
                    ->get();

                foreach ($leases as $lease) {
                    /** @var \App\Models\Lease $lease */
                    $client = $lease->client;

                    if (!$client || (empty($client->email) && empty($client->phone))) {
                        continue;
                    }

                    // Check if rent is unpaid for current month
                    $currentDate = now();
                    $hasPaidThisMonth = $lease->payments()
                        ->whereYear('paid_at', $currentDate->year)
                        ->whereMonth('paid_at', $currentDate->month)
                        ->where('status', 'completed')
                        ->exists();

                    if ($hasPaidThisMonth) {
                        continue;
                    }

                    // Calculate days overdue (rent due on 1st of month)
                    $dueDate = now()->startOfMonth();
                    $daysOverdue = now()->diffInDays($dueDate);

                    // Send notification if overdue matches the configured days
                    if ($daysOverdue >= $days && $daysOverdue < $days + 1) {
                        $client->notify(new OverduePaymentNotification($lease, $daysOverdue, $level));
                        $totalSent++;
                        $contact = $client->email ?? $client->phone;
                        $this->info("[{$level}] Relance envoyée à {$contact} ({$daysOverdue}j de retard)");
                    }
                }
            } finally {
                Tenancy::end();
            }
        });

        $this->info("Envoyé {$totalSent} relances [{$level}].");
        return Command::SUCCESS;
    }
}
