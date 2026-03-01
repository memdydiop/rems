<?php

declare(strict_types=1);

namespace App\Jobs;


use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $company,
        public string $subdomain,
        public string $name,
        public string $email,
        public string $password, // New parameter
        public string $plan = 'free'
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Use the first configured central domain or fallback to localhost
        $centralDomain = config('tenancy.central_domains')[0] ?? 'localhost';
        $fullDomain = $this->subdomain . '.' . $centralDomain;

        DB::transaction(function () use ($fullDomain) {
            // Prevent actual database creation during unit tests if running in sqlite :memory:
            $isUnitTest = config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:';

            $creationCallback = function () {
                return Tenant::create([
                    'id' => $this->subdomain,
                    'tenancy_db_name' => 'tenant_' . $this->subdomain,
                    'company' => $this->company,
                    'plan' => $this->plan,
                    'trial_ends_at' => now()->addDays(14),
                ]);
            };

            if ($isUnitTest) {
                $tenant = Tenant::withoutEvents($creationCallback);
            } else {
                $tenant = $creationCallback();
            }

            $tenant->domains()->create([
                'domain' => $fullDomain,
            ]);

            $tenant->run(function () use ($fullDomain) {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password), // Use provided password
                ]);

                $appUrl = config('app.url');
                $parsedUrl = parse_url($appUrl);
                $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
                $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : ($this->isSecure() ? 'https://' : 'http://');

                // Send welcome email with login link
                $tenantUrl = $scheme . $fullDomain . $port . '/login';

                $user->notify(new \App\Notifications\WelcomeTenantNotification($tenantUrl));
            });

        });
    }

    /**
     * Determine if the application is running via HTTPS.
     */
    protected function isSecure(): bool
    {
        return app()->environment('production') || request()->secure();
    }
}
