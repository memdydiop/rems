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
    protected $description = 'Send overdue payment relance notifications to renters';

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
                    ->with(['renter', 'unit.property', 'payments'])
                    ->get();

                foreach ($leases as $lease) {
                    /** @var \App\Models\Lease $lease */
                    $renter = $lease->renter;

                    if (!$renter || (empty($renter->email) && empty($renter->phone))) {
                        continue;
                    }

                    // Check if rent is unpaid for current month
                    $currentMonth = now()->format('Y-m');
                    $hasPaidThisMonth = $lease->payments()
                        ->whereRaw("to_char(paid_at, 'YYYY-MM') = ?", [$currentMonth])
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
                        $renter->notify(new OverduePaymentNotification($lease, $daysOverdue, $level));
                        $totalSent++;
                        $contact = $renter->email ?? $renter->phone;
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
