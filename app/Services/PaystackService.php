<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected string $baseUrl = 'https://api.paystack.co';

    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key', env('PAYSTACK_SECRET_KEY')) ?? '';
    }

    /**
     * Initialize a transaction (Standard Checkout)
     */
    public function initializeTransaction(string $email, int $amount, array $metadata = [], ?string $callbackUrl = null, string $currency = 'NGN')
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->post("{$this->baseUrl}/transaction/initialize", [
                    'email' => $email,
                    'amount' => $amount, // In kobo/cents
                    'metadata' => $metadata,
                    'callback_url' => $callbackUrl,
                    'currency' => $currency,
                    'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'],
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Paystack Error: ' . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify a transaction
     */
    public function verifyTransaction(string $reference)
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        return $response->json();
    }

    /**
     * Create a Subscription
     * Usually Paystack does this automatically if a plan code is passed during initialization.
     * But we can also check subscription status api.
     */

    // Plan management not strictly needed if we assume plans are created in Paystack Dashboard and synced to DB manually or via command.
    // For simplicity, we just initialize payments with plan codes.
}
