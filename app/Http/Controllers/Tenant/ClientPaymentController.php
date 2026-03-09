<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Client;
use App\Models\RentPayment;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClientPaymentController extends Controller
{
    /**
     * Show the payment page.
     */
    public function show(Request $request)
    {
        $client = Client::where('user_id', auth()->id())->first();

        if (!$client) {
            abort(403, 'Aucun profil client associé.');
        }

        $lease = $client->leases()
            ->where('status', 'active')
            ->with(['unit.property'])
            ->first();

        if (!$lease) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Aucun bail actif trouvé.');
        }

        // Check if already paid this month
        $currentDate = now();
        $alreadyPaid = $lease->payments()
            ->whereYear('paid_at', $currentDate->year)
            ->whereMonth('paid_at', $currentDate->month)
            ->where('status', PaymentStatus::Completed)
            ->exists();

        return view('pages.client.pay', [
            'client' => $client,
            'lease' => $lease,
            'alreadyPaid' => $alreadyPaid,
        ]);
    }

    /**
     * Initialize Paystack payment.
     */
    public function initialize(Request $request)
    {
        $client = Client::where('user_id', auth()->id())->firstOrFail();
        $lease = $client->leases()->where('status', 'active')->firstOrFail();

        $reference = 'PMS-' . strtoupper(substr($lease->id, 0, 8)) . '-' . now()->format('YmdHis');
        $amountInKobo = (int) ($lease->rent_amount * 100);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $client->email,
                'amount' => $amountInKobo,
                'currency' => 'XOF',
                'reference' => $reference,
                'callback_url' => route('client.pay.callback'),
                'metadata' => [
                    'lease_id' => $lease->id,
                    'client_id' => $client->id,
                    'tenant_id' => tenant('id'),
                ],
            ]);

        if ($response->successful() && $response->json('status')) {
            return redirect($response->json('data.authorization_url'));
        }

        return back()->with('error', 'Impossible d\'initialiser le paiement. Veuillez réessayer.');
    }

    /**
     * Handle Paystack callback.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Référence de paiement manquante.');
        }

        // Verify transaction with Paystack
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (!$response->successful() || !$response->json('status')) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Échec de la vérification du paiement.');
        }

        $data = $response->json('data');

        if ($data['status'] !== 'success') {
            return redirect()->route('client.dashboard')
                ->with('error', 'Le paiement n\'a pas abouti.');
        }

        $metadata = $data['metadata'] ?? [];
        $leaseId = $metadata['lease_id'] ?? null;
        $clientId = $metadata['client_id'] ?? null;

        if (!$leaseId) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Données de paiement invalides.');
        }

        // Create the rent payment record
        $payment = RentPayment::create([
            'lease_id' => $leaseId,
            'amount' => $data['amount'] / 100,
            'paid_at' => now(),
            'method' => 'paystack',
            'status' => PaymentStatus::Completed->value,
            'notes' => 'Paiement en ligne — Réf: ' . $reference,
        ]);

        // Send receipt notification
        $client = Client::find($clientId);
        if ($client) {
            $client->notify(new PaymentReceivedNotification($payment));
        }

        return redirect()->route('client.dashboard')
            ->with('success', 'Paiement effectué avec succès ! Votre quittance sera disponible sous peu.');
    }
}
