<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\RentPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    /**
     * Generate and download a lease contract PDF.
     */
    public function leaseContract(Lease $lease): Response
    {
        $lease->load(['client', 'unit.property']);

        $unit = $lease->unit;
        $property = $unit->property;

        $pdf = Pdf::loadView('pdf.lease-contract', [
            'lease' => $lease,
            'unit' => $unit,
            'property' => $property,
        ]);

        $filename = 'contrat-bail-' . $unit->name . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate and download a payment receipt PDF.
     */
    public function paymentReceipt(RentPayment $payment): Response
    {
        $payment->load(['lease.client', 'lease.unit.property']);

        $lease = $payment->lease;
        $client = $lease->client;
        $unit = $lease->unit;
        $property = $unit->property;

        $pdf = Pdf::loadView('pdf.payment-receipt', [
            'payment' => $payment,
            'lease' => $lease,
            'client' => $client,
            'unit' => $unit,
            'property' => $property,
        ]);

        $filename = 'recu-paiement-' . strtoupper(substr($payment->id, 0, 8)) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate and download a rent receipt (quittance de loyer) PDF.
     */
    public function rentReceipt(RentPayment $payment): Response
    {
        $payment->load(['lease.client', 'lease.unit.property']);

        $lease = $payment->lease;
        $client = $lease->client;
        $unit = $lease->unit;
        $property = $unit->property;

        $pdf = Pdf::loadView('pdf.rent-receipt', [
            'payment' => $payment,
            'lease' => $lease,
            'client' => $client,
            'unit' => $unit,
            'property' => $property,
        ]);

        $filename = 'quittance-' . strtoupper(substr($payment->id, 0, 8)) . '.pdf';

        return $pdf->download($filename);
    }
}
