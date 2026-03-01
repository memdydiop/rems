<?php

namespace App\Http\Controllers\Tenant;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Renter;
use App\Models\RentPayment;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RenterPaymentController extends Controller
{
    /**
     * Show the payment page.
     */
    public function show(Request $request)
    {
        $renter = Renter::where('user_id', auth()->id())->first();

        if (!$renter) {
            abort(403, 'Aucun profil locataire associé.');
        }

        $lease = $renter->leases()
            ->where('status', 'active')
            ->with(['unit.property'])
            ->first();

        if (!$lease) {
            return redirect()->route('renter.dashboard')
                ->with('error', 'Aucun bail actif trouvé.');
        }

        // Check if already paid this month
        $currentMonth = now()->format('Y-m');
        $alreadyPaid = $lease->payments()
            ->whereRaw("to_char(paid_at, 'YYYY-MM') = ?", [$currentMonth])
            ->where('status', PaymentStatus::Completed)
            ->exists();

        return view('pages.renter.pay', [
            'renter' => $renter,
            'lease' => $lease,
            'alreadyPaid' => $alreadyPaid,
        ]);
    }

    /**
     * Initialize Paystack payment.
     */
    public function initialize(Request $request)
    {
        $renter = Renter::where('user_id', auth()->id())->firstOrFail();
        $lease = $renter->leases()->where('status', 'active')->firstOrFail();

        $reference = 'PMS-' . strtoupper(substr($lease->id, 0, 8)) . '-' . now()->format('YmdHis');
        $amountInKobo = (int) ($lease->rent_amount * 100);

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $renter->email,
                'amount' => $amountInKobo,
                'currency' => 'XOF',
                'reference' => $reference,
                'callback_url' => route('renter.pay.callback'),
                'metadata' => [
                    'lease_id' => $lease->id,
                    'renter_id' => $renter->id,
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
            return redirect()->route('renter.dashboard')
                ->with('error', 'Référence de paiement manquante.');
        }

        // Verify transaction with Paystack
        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (!$response->successful() || !$response->json('status')) {
            return redirect()->route('renter.dashboard')
                ->with('error', 'Échec de la vérification du paiement.');
        }

        $data = $response->json('data');

        if ($data['status'] !== 'success') {
            return redirect()->route('renter.dashboard')
                ->with('error', 'Le paiement n\'a pas abouti.');
        }

        $metadata = $data['metadata'] ?? [];
        $leaseId = $metadata['lease_id'] ?? null;
        $renterId = $metadata['renter_id'] ?? null;

        if (!$leaseId) {
            return redirect()->route('renter.dashboard')
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
        $renter = Renter::find($renterId);
        if ($renter) {
            $renter->notify(new PaymentReceivedNotification($payment));
        }

        return redirect()->route('renter.dashboard')
            ->with('success', 'Paiement effectué avec succès ! Votre quittance sera disponible sous peu.');
    }
}
