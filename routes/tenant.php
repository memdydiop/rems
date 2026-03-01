<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
    SubstituteBindings::class,
])->group(function () {
    require base_path('vendor/laravel/fortify/routes/routes.php');

    // Impersonation Leave Route
    Route::get('/impersonate/leave', function () {
        // Check if we are impersonating
        if (!session()->has('impersonating_from_central')) {
            return redirect('/');
        }

        // Clear the session marker
        session()->forget('impersonating_from_central');

        // Find the central domain
        $centralDomain = config('tenancy.central_domains')[0];
        $protocol = request()->secure() ? 'https' : 'http';
        $port = request()->getPort();

        // Construct the central URL to leave impersonation properly
        $centralUrl = $protocol . '://' . $centralDomain . ($port !== 80 && $port !== 443 ? ":$port" : '') . '/impersonate/leave?token=' . request()->session()->getId();

        header("Location: $centralUrl");
        exit;
    })->name('tenancy.impersonate.leave');

    // Impersonation Entry Route
    Route::get('/impersonate/{token}', function ($token) {
        $response = \Stancl\Tenancy\Features\UserImpersonation::makeResponse($token);
        session()->put('impersonating_from_central', true);
        return $response;
    });

    Route::middleware('auth')->group(function () {
        Route::middleware([
            \App\Http\Middleware\EnsureSubscriptionActive::class,
            \App\Http\Middleware\CheckOnboardingStatus::class,
        ])->group(function () {
            Route::livewire('/', 'pages::tenant.dashboard')->name('dashboard');
            Route::livewire('/properties', 'pages::tenant.properties.index')->name('tenant.properties.index');
            Route::livewire('/properties/{property}', 'pages::tenant.properties.show')->name('tenant.properties.show');
            Route::livewire('/units', 'pages::tenant.units.index')->name('tenant.units.index');
            Route::livewire('/units/{unit}', 'pages::tenant.units.show')->name('tenant.units.show');
            Route::livewire('/renters', 'pages::tenant.renters.index')->name('tenant.renters.index');
            Route::livewire('/projects', 'pages::tenant.projects.index')->name('tenant.projects.index');
            Route::livewire('/projects/{project}', 'pages::tenant.projects.show')->name('tenant.projects.show');
            Route::livewire('/maintenance', 'pages::tenant.maintenance.index')->name('tenant.maintenance.index');
            Route::livewire('/settings/activity', 'pages::tenant.settings.activity')->name('tenant.settings.activity');
            Route::livewire('/expenses', 'pages::tenant.expenses.index')->name('tenant.expenses.index');
            Route::livewire('/settings/billing', 'pages::tenant.settings.billing')->name('tenant.settings.billing');
            Route::livewire('/settings/members', 'pages::tenant.settings.members')->name('tenant.settings.members');
            Route::livewire('/settings/vendors', 'pages::tenant.settings.vendors.index')->name('tenant.settings.vendors.index');
            Route::livewire('/settings/emails', 'pages::tenant.settings.emails')->name('tenant.settings.emails');
            Route::livewire('/onboarding', 'pages::tenant.onboarding')->name('tenant.onboarding');

            // Roles
            Route::prefix('settings/roles')->name('tenant.settings.roles.')->group(function () {
                Route::livewire('/', 'pages::tenant.settings.roles.index')->name('index');
            });

            Route::livewire('/leases', 'pages::tenant.leases.index')->name('tenant.leases.index');
            Route::livewire('/leases/create', 'pages::tenant.leases.create')->name('tenant.leases.create');

            // Reports
            Route::prefix('reports')->name('tenant.reports.')->group(function () {
                Route::livewire('/', 'pages::tenant.reports.index')->name('index');
                Route::livewire('/revenue', 'pages::tenant.reports.revenue')->name('revenue');
                Route::livewire('/properties', 'pages::tenant.reports.properties')->name('properties');
                Route::livewire('/occupancy', 'pages::tenant.reports.occupancy')->name('occupancy');
                Route::livewire('/payments', 'pages::tenant.reports.payments')->name('payments');
            });

            // PDF Downloads
            Route::prefix('pdf')->name('tenant.pdf.')->group(function () {
                Route::get('/lease/{lease}', [\App\Http\Controllers\Tenant\PdfController::class, 'leaseContract'])->name('lease');
                Route::get('/payment/{payment}', [\App\Http\Controllers\Tenant\PdfController::class, 'paymentReceipt'])->name('payment');
                Route::get('/receipt/{payment}', [\App\Http\Controllers\Tenant\PdfController::class, 'rentReceipt'])->name('receipt');
            });

            // Owners
            Route::livewire('/owners', 'pages::tenant.owners.index')->name('tenant.owners.index');
        });

        // Billing Callback - Must be accessible without active subscription
        Route::livewire('/billing/callback', 'pages::tenant.settings.callback')->name('tenant.billing.callback');

        // Auth & Settings
        require __DIR__ . '/settings.php';
    });

    Route::livewire('/join/{token}', 'pages::tenant.auth.join')->name('tenant.join');



    // Renter Portal Routes
    Route::middleware(['auth', \App\Http\Middleware\RenterAccess::class])
        ->prefix('renter')
        ->name('renter.')
        ->group(function () {
            Route::livewire('/', 'pages::renter.dashboard')->name('dashboard');
            Route::livewire('/payments', 'pages::renter.payments')->name('payments');
            Route::get('/pay', [\App\Http\Controllers\Tenant\RenterPaymentController::class, 'show'])->name('pay');
            Route::post('/pay/initialize', [\App\Http\Controllers\Tenant\RenterPaymentController::class, 'initialize'])->name('pay.initialize');
            Route::get('/pay/callback', [\App\Http\Controllers\Tenant\RenterPaymentController::class, 'callback'])->name('pay.callback');
        });

    // Owner Portal Routes
    Route::middleware(['auth', \App\Http\Middleware\OwnerAccess::class])
        ->prefix('owner')
        ->name('owner.')
        ->group(function () {
            Route::livewire('/', 'pages::owner.dashboard')->name('dashboard');
            Route::get('/report/{year}/{month}', [\App\Http\Controllers\Tenant\OwnerPdfController::class, 'download'])->name('report');
        });

});

Route::middleware([
    'api',
    'throttle:api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api')->group(function () {
    // Public Tenant API Routes
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    // Protected Tenant API Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (\Illuminate\Http\Request $request) {
            return $request->user();
        });

        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

        // Resources (Full CRUD)
        Route::apiResource('properties', \App\Http\Controllers\Api\PropertyController::class);
        Route::apiResource('units', \App\Http\Controllers\Api\UnitController::class);
        Route::apiResource('renters', \App\Http\Controllers\Api\RenterController::class);
        Route::apiResource('leases', \App\Http\Controllers\Api\LeaseController::class);
        Route::apiResource('tasks', \App\Http\Controllers\Api\TaskController::class)->only(['index', 'show', 'update']);
        Route::apiResource('expenses', \App\Http\Controllers\Api\ExpenseController::class);
        Route::apiResource('maintenance-requests', \App\Http\Controllers\Api\MaintenanceRequestController::class);
        Route::apiResource('owners', \App\Http\Controllers\Api\OwnerController::class);
        Route::apiResource('rent-payments', \App\Http\Controllers\Api\RentPaymentController::class);
    });
});
