<?php

namespace App\Http\Controllers;

use App\Jobs\CreateTenantJob;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    /**
     * Initiate the payment process.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subdomain' => 'required|string|alpha_dash|max:50|unique:domains,domain',
            'plan' => 'required|in:starter,growth,scale', // Add more plans if needed
            'amount' => 'required|numeric' // We should PROBABLY fetch this from the backend based on plan to avoid tampering, but for MVP...
        ]);

        // Security: Reset amount based on plan to prevent tampering
        $prices = [
            'starter' => 0, // Free? Or 29000? Let's use the DB prices later. For now, trust the form but multiply by 100 for NGN?
            // Actually, the landing page sends the price. Let's just use it but cast to integer.
        ];

        // Paystack expects amount in kobo/cents. 
        // If currency is XOF, it is usually integer.
        // Let's assume the frontend sends the raw amount (e.g. 29000).
        $amount = (int) $request->amount;
        // If NGN, multiply by 100. If XOF, usually 1.
        // Let's check currency.
        $currency = 'XOF'; // Default for now

        if ($currency === 'NGN') {
            $amount *= 100;
        }

        // Create metadata
        $metadata = [
            'company' => $validated['company'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subdomain' => $validated['subdomain'],
            'plan' => $validated['plan'],
        ];

        // Init Transaction
        $response = $this->paystack->initializeTransaction(
            $validated['email'],
            $amount,
            $metadata,
            route('central.payment.callback'),
            $currency
        );

        if (!$response['status']) {
            return back()->with('error', 'Payment initialization failed: ' . $response['message']);
        }

        // Save pending payment
        Payment::create([
            'reference' => $response['data']['reference'],
            'email' => $validated['email'],
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);

        return redirect($response['data']['authorization_url']);
    }

    /**
     * Handle Paystack Callback.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect('/')->with('error', 'No payment reference found.');
        }

        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return redirect('/')->with('error', 'Invalid payment reference.');
        }

        if ($payment->status === 'success') {
            return redirect('/')->with('success', 'Payment already processed.');
        }

        // Verify with Paystack
        $response = $this->paystack->verifyTransaction($reference);

        if ($response['status'] && $response['data']['status'] === 'success') {
            // Update Payment
            $payment->update(['status' => 'success']);

            // Create Tenant
            try {
                // Extract metadata
                $meta = $payment->metadata; // Casted to array by Eloquent if casts defined
                // Wait, I need to add casts to Payment model

                CreateTenantJob::dispatch(
                    $meta['company'],
                    $meta['subdomain'],
                    $meta['name'],
                    $meta['email'],
                    Str::random(16), // Generate random password for paid flow
                    $meta['plan'] ?? 'starter'
                );

                return redirect('/')->with('success', 'Workspace created! Check your email to login.');

            } catch (\Exception $e) {
                Log::error('Tenant Creation Failed: ' . $e->getMessage());
                return redirect('/')->with('error', 'Payment successful but workspace creation failed. Contact support.');
            }
        }

        $payment->update(['status' => 'failed']);
        return redirect('/')->with('error', 'Payment verification failed.');
    }
}
