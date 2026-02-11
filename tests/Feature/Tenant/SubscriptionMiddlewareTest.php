<?php

namespace Tests\Feature\Tenant;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    // Actually, for Middleware tests we define tenant context.

    protected $tenant;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // We let each test setup its own tenant state
    }

    public function test_allows_access_during_active_trial()
    {
        // 1. Setup Tenant in Trial
        $this->tenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'trial_tenant',
                'tenancy_db_name' => 'trial_tenant_db',
                'plan' => 'free',
                'trial_ends_at' => now()->addDays(5), // Future
            ]);
        });

        tenancy()->initialize($this->tenant);

        // 2. Mock Request & Next Closure
        $request = \Illuminate\Http\Request::create('/dashboard', 'GET');
        $next = function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        };

        // 3. Run Middleware
        $middleware = new \App\Http\Middleware\EnsureSubscriptionActive();
        $response = $middleware->handle($request, $next);

        // 4. Assert Access Granted
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_blocks_access_when_trial_expired_and_no_subscription()
    {
        // 1. Setup Expired Tenant
        $this->tenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'expired_tenant',
                'tenancy_db_name' => 'expired_tenant_db',
                'plan' => 'free',
                'trial_ends_at' => now()->subDay(), // Past
            ]);
        });

        tenancy()->initialize($this->tenant);

        // 2. Mock Request
        $request = \Illuminate\Http\Request::create('/dashboard', 'GET');

        $route = new \Illuminate\Routing\Route('GET', '/dashboard', []);
        $route->name('tenant.dashboard'); // Name it something that is NOT billing
        $request->setRouteResolver(fn() => $route);

        // Middleware checks routeIs(), which uses the route resolver. 
        // Simpler approach: Verify redirect.

        $next = function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        };

        $middleware = new \App\Http\Middleware\EnsureSubscriptionActive();
        $response = $middleware->handle($request, $next);

        // 3. Assert Redirect to Billing
        $this->assertEquals(302, $response->getStatusCode());
        // Verify route generation might be tricky in unit test without full app routing, 
        // but let's see if Redirect::route works if routes are loaded. 
        // Since we extend TestCase, full app is loaded.
    }

    public function test_allows_access_to_billing_page_even_if_expired()
    {
        // 1. Setup Expired Tenant
        $this->tenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'expired_tenant_billing',
                'tenancy_db_name' => 'expired_tenant_billing_db',
                'plan' => 'free',
                'trial_ends_at' => now()->subDay(),
            ]);
        });

        tenancy()->initialize($this->tenant);

        // 2. Mock Request to Billing Page
        $request = \Illuminate\Http\Request::create('/settings/billing', 'GET');

        // We need to match the route name logic: $request->routeIs(...)
        // RouteIs checks the current route. In a unit test, we often don't have a route.
        // We need to bind a route to the request.

        $route = new \Illuminate\Routing\Route('GET', '/settings/billing', []);
        $route->name('tenant.settings.billing');
        $request->setRouteResolver(fn() => $route);

        $next = function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        };

        $middleware = new \App\Http\Middleware\EnsureSubscriptionActive();
        $response = $middleware->handle($request, $next);

        // 3. Assert Access Granted
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_allows_access_with_active_subscription_even_if_trial_expired()
    {
        // 1. Setup Expired Trial Tenant
        $this->tenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'paid_tenant',
                'tenancy_db_name' => 'paid_tenant_db',
                'plan' => 'Growth',
                'trial_ends_at' => now()->subDay(),
            ]);
        });

        // 2. Add Active Subscription
        // Setup Plan
        $plan = Plan::create(['name' => 'Growth', 'amount' => 100, 'paystack_code' => 'P1', 'interval' => 'monthly', 'currency' => 'USD']);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        tenancy()->initialize($this->tenant);

        $request = \Illuminate\Http\Request::create('/dashboard', 'GET');
        $next = function ($req) {
            return new \Illuminate\Http\Response('OK', 200);
        };

        $middleware = new \App\Http\Middleware\EnsureSubscriptionActive();
        $response = $middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
