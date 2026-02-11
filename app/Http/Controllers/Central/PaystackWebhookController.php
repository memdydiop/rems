<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify signature (HMAC SHA512)
        $secret = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY'));
        $signature = $request->header('x-paystack-signature');

        if (!$signature || hash_hmac('sha512', $request->getContent(), $secret) !== $signature) {
            Log::warning('Paystack Webhook Signature Mismatch');

            return response()->json(['status' => 'error'], 401);
        }

        $payload = $request->all();
        $event = $payload['event'];

        Log::info('Paystack Event Received: ' . $event);

        if ($event === 'charge.success') {
            $this->handleChargeSuccess($payload['data']);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleChargeSuccess(array $data)
    {
        // Metadata contains tenant_id and plan_id
        $metadata = $data['metadata'] ?? [];
        $tenantId = $metadata['tenant_id'] ?? null;
        $planId = $metadata['plan_id'] ?? null;

        if (!$tenantId || !$planId) {
            Log::error('Paystack Charge Success: Missing metadata');

            return;
        }

        $tenant = Tenant::find($tenantId);
        $plan = Plan::find($planId);

        if ($tenant && $plan) {
            // CRITICAL: Update Tenant Plan Column for Feature Gating
            $tenant->update(['plan' => $plan->name]);

            // Update or Create Subscription
            $sub = Subscription::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'paystack_id' => $data['id'], // Transaction ID or Subscription Code if recurring
                    'paystack_code' => $data['authorization']['authorization_code'] ?? null,
                    'starts_at' => now(),
                    'ends_at' => now()->add(
                        $plan->interval === 'annually' ? '1 year' : '1 month'
                    ),
                    'email_token' => $data['customer']['email'] ?? null,
                ]
            );

            Log::info("Subscription activated for Tenant {$tenant->id}");
        }
    }
}
