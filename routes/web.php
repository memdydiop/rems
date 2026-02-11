<?php

use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->name('central.')->group(function () {

        // -- Auth & Settings --
        require base_path('vendor/laravel/fortify/routes/routes.php');
        require __DIR__ . '/settings.php';


        // -- Public Routes --
        // -- Public Routes --
        Route::livewire('/', 'pages::central.landing')->name('home');

        Route::get('/impersonate/leave', function () {
            // Remove the session marker if it exists
            session()->forget('impersonating_from_central');

            return redirect()->route('central.tenants.index');
        })->name('impersonate.leave');


        Route::redirect('/pricing', '/#pricing')->name('pricing');
        Route::livewire('/invitation/{token}', 'pages::central.auth.accept-invitation')->name('invitation.accept');
        Route::post('/paystack/webhook', [\App\Http\Controllers\Central\PaystackWebhookController::class, 'handle'])->name('paystack.webhook');

        // Payment Routes (Restored)
        Route::post('/pay', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payment.store');
        Route::get('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback')->name('central.payment.callback'); // Alias for PaystackService compatibility


        // -- Protected Routes --
        Route::middleware(['auth', 'verified'])->group(function () {
            Route::livewire('/dashboard', 'pages::central.dashboard')->name('dashboard');




            // Impersonation
            Route::get('/impersonate/{tenant}', function ($tenantId) {
                $tenant = \App\Models\Tenant::find($tenantId);

                if (!$tenant) {
                    Flux\Flux::toast('Client introuvable.', 'danger');
                    return back();
                }

                // Find a user to impersonate (e.g., the first user)
                $user = $tenant->run(function () {
                    return \App\Models\User::first();
                });

                if (!$user) {
                    Flux\Flux::toast('No users found for this tenant.', 'danger');
                    return back();
                }

                $token = tenancy()->impersonate($tenant, $user->id, '/');

                // Get the tenant's primary domain
                $domain = $tenant->domains->first()?->domain;

                if (!$domain) {
                    Flux\Flux::toast('No domain found for this tenant.', 'danger');
                    return back();
                }

                $protocol = request()->secure() ? 'https' : 'http';
                $port = request()->getPort();
                $portPart = '';

                if (($protocol === 'http' && $port !== 80) || ($protocol === 'https' && $port !== 443)) {
                    $portPart = ':' . $port;
                }

                $url = $protocol . '://' . $domain . $portPart . '/impersonate/' . $token->token;

                // Set a session marker for UX
                session()->put('impersonating_from_central', true);

                return redirect()->away($url);
            })->name('impersonate');



            // Tenant Management
            Route::prefix('tenants')->name('tenants.')->group(function () {
                Route::livewire('/', 'pages::central.tenants.index')->name('index');
                Route::livewire('/{tenant}', 'pages::central.tenants.show')->name('show');
            });

            Route::livewire('/users', 'pages::central.users.index')->name('users.index');

            // Reports
            Route::livewire('/reports', 'pages::central.reports.index')->name('reports.index');

            // Activity Logs
            Route::livewire('/activity', 'pages::central.activity.index')->name('activity.index');

            // Plans
            Route::middleware(['can:manage plans'])->group(function () {
                Route::livewire('/plans', 'pages::central.plans.index')->name('plans.index');
            });

            // Settings
            Route::prefix('settings')->name('settings.')->group(function () {
                // Roles
                Route::prefix('roles')->name('roles.')->group(function () {
                    Route::livewire('/', 'pages::central.settings.roles.index')->name('index');
                });
            });
        });
    });
}
