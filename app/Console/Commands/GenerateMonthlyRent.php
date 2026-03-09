<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Lease;
use App\Enums\PaymentStatus;
use Carbon\Carbon;

class GenerateMonthlyRent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pms:generate-monthly-rent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate pending rent payments for all active leases for the current month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly rent generation...');

        $tenants = Tenant::all();
        $generatedCount = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $this->info("Processing tenant: {$tenant->id}");

            $activeLeases = Lease::where('status', 'active')->get();
            $startOfMonth = Carbon::now()->startOfMonth();

            foreach ($activeLeases as $lease) {
                // Check if a payment for this month and year already exists
                $existingPayment = $lease->payments()
                    ->whereYear('paid_at', $startOfMonth->year)
                    ->whereMonth('paid_at', $startOfMonth->month)
                    ->first();

                if (!$existingPayment) {
                    $totalRent = $lease->rent_amount + $lease->charges_amount;

                    $lease->payments()->create([
                        'amount' => $totalRent,
                        'paid_at' => $startOfMonth,
                        'method' => 'cash', // Default placeholder
                        'status' => PaymentStatus::Pending,
                        'notes' => 'Loyer ' . $startOfMonth->translatedFormat('F Y'),
                    ]);

                    $generatedCount++;
                    $this->line(" - Generated payment for lease: {$lease->id}");
                }
            }

            tenancy()->end();
        }

        $this->info("Completed. Generated {$generatedCount} missing rent payments.");
    }
}
