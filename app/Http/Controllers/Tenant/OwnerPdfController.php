<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\RentPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OwnerPdfController extends Controller
{
    public function download(Request $request, $year, $month)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $owner = Owner::where('email', $user->email)->orWhere('user_id', $user->id)->firstOrFail();

        // Parse date for filtering
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 1. Get all payments for this owner's properties in this month
        $payments = RentPayment::query()
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->whereHas('lease.unit.property', function ($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })
            ->with(['lease.unit.property', 'lease.renter'])
            ->get();

        // 2. Aggregate Data
        $details = [];
        $totalCollected = 0;
        $commissionRate = 10; // Hardcoded 10% for now (MVP)

        foreach ($payments as $payment) {
            $lease = $payment->lease;
            $unit = $lease->unit;
            $property = $unit->property;

            $amount = $payment->amount; // Assuming this is purely rent for MVP
            $commission = $amount * ($commissionRate / 100);

            $details[] = [
                'property_name' => $property->name,
                'address' => $property->address,
                'unit_name' => $unit->name,
                'renter_name' => $lease->renter->first_name . ' ' . $lease->renter->last_name,
                'rent_collected' => $amount,
                'commission' => $commission,
            ];

            $totalCollected += $amount;
        }

        $totalCommission = $totalCollected * ($commissionRate / 100);
        $totalNet = $totalCollected - $totalCommission;

        // 3. Generate PDF
        $pdf = Pdf::loadView('pdf.owner-report', [
            'owner' => $owner,
            'year' => $year,
            'monthName' => $startDate->locale('fr')->monthName,
            'details' => $details,
            'totalCollected' => $totalCollected,
            'commissionRate' => $commissionRate,
            'totalCommission' => $totalCommission,
            'totalNet' => $totalNet,
        ]);

        $filename = "CRG-{$year}-{$month}.pdf";

        return $pdf->download($filename);
    }
}
