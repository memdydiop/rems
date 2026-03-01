<?php

use Illuminate\Support\Facades\Route;

// -- Routes d'authentification (Fortify) pour le Central Domain --
// Pour éviter la définition multiple des routes (ex. central.login) si plusieurs central_domains existent.
$mainCentralDomain = config('tenancy.central_domains')[0] ?? config('app.url');
if ($mainCentralDomain) {
    Route::domain($mainCentralDomain)->name('central.')->group(function () {
        require base_path('vendor/laravel/fortify/routes/routes.php');
        require __DIR__ . '/settings.php';
    });
}

// -- Main Central Routes (Defined only once on the primary central domain) --
$centralDomains = config('tenancy.central_domains');
$primaryDomain = $centralDomains[0] ?? config('app.url');

Route::domain($primaryDomain)->name('central.')->group(function () {

    // -- Public Routes --
    Route::livewire('/', 'pages::central.landing')->name('home');

    Route::get('/impersonate/leave', function () {
        session()->forget('impersonating_from_central');
        return redirect()->route('central.tenants.index');
    })->name('impersonate.leave');

    Route::redirect('/pricing', '/#pricing')->name('pricing');
    Route::livewire('/invitation/{token}', 'pages::central.auth.accept-invitation')->name('invitation.accept');
    Route::post('/paystack/webhook', [\App\Http\Controllers\Central\PaystackWebhookController::class, 'handle'])->name('paystack.webhook');

    // Payment Routes
    Route::post('/pay', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payment.store');
    Route::get('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');


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
