<?php

namespace Tests\Feature\Central;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_missing_signature()
    {
        $response = $this->postJson(route('central.paystack.webhook'), [
            'event' => 'charge.success',
            'data' => []
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        Config::set('services.paystack.secret_key', 'test_secret');

        $response = $this->postJson(route('central.paystack.webhook'), [
            'event' => 'charge.success',
            'data' => []
        ], ['x-paystack-signature' => 'invalid_signature']);

        $response->assertStatus(401);
    }

    public function test_webhook_processes_charge_success_and_syncs_plan()
    {
        Config::set('services.paystack.secret_key', 'test_secret');

        // Setup Plan and Tenant
        $plan = Plan::create([
            'name' => 'Growth',
            'amount' => 5000,
            'interval' => 'monthly',
            'currency' => 'USD',
            'paystack_code' => 'PLN_growth',
        ]);

        $tenant = Tenant::withoutEvents(function () {
            return Tenant::create([
                'id' => 'test_tenant',
                'tenancy_db_name' => 'test_tenant_db',
                'plan' => 'free', // Starts on Free plan
                'company' => 'Test Company' // Add required fields if any
            ]);
        });
        // Domain creation is not strictly needed for the webhook logic, ensuring isolation.

        // Payload
        $payload = [
            'event' => 'charge.success',
            'data' => [
                'id' => 123456,
                'status' => 'success',
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                ],
                'authorization' => [
                    'authorization_code' => 'AUTH_12345',
                ],
                'customer' => [
                    'email' => 'test@example.com',
                ],
            ],
        ];

        // Calculate Signature
        $content = json_encode($payload);
        $signature = hash_hmac('sha512', $content, 'test_secret');

        // Act
        $response = $this->call(
            'POST',
            route('central.paystack.webhook'),
            [],
            [],
            [],
            [
                'HTTP_x-paystack-signature' => $signature,
                'CONTENT_TYPE' => 'application/json'
            ],
            $content
        );

        $response->assertStatus(200);

        // Assert Subscription Created
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'paystack_id' => 123456,
        ]);

        // Assert CRITICAL Fix: Tenant Plan Updated (in JSON data)
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'data->plan' => 'Growth',
        ]);
    }
}
