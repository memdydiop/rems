<?php

namespace App\Console\Commands;

use App\Mail\LeaseExpiringMail;
use App\Models\Lease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendLeaseExpirationAlerts extends Command
{
    protected $signature = 'pms:send-lease-alerts {--days=30 : Days before expiration to send alert}';
    protected $description = 'Send lease expiration alerts to property managers';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $expirationDate = now()->addDays($days);

        $leases = Lease::where('status', 'active')
            ->whereDate('end_date', '<=', $expirationDate)
            ->whereDate('end_date', '>', now())
            ->with(['renter', 'unit.property'])
            ->get();

        $sent = 0;

        foreach ($leases as $lease) {
            // Get property owner email or default admin
            $recipientEmail = $lease->unit->property->owner_email
                ?? config('mail.from.address');

            if ($recipientEmail) {
                Mail::to($recipientEmail)->send(new LeaseExpiringMail(
                    lease: $lease
                ));
                $sent++;
            }
        }

        $this->info("Sent {$sent} lease expiration alerts.");
        return Command::SUCCESS;
    }
}
