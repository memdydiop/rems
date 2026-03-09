<?php

namespace App\Exports;

use App\Models\RentPayment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected string $year = '',
        protected string $month = '',
        protected string $status = '',
    ) {
    }

    public function query()
    {
        return RentPayment::query()
            ->with(['lease.client', 'lease.unit.property'])
            ->when($this->year, fn($q) => $q->whereYear('paid_at', $this->year))
            ->when($this->month, fn($q) => $q->whereMonth('paid_at', $this->month))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderByDesc('paid_at');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Client',
            'Période Début',
            'Période Fin',
            'Propriété',
            'Unité',
            'Montant (FCFA)',
            'Méthode',
            'Statut',
            'Notes',
        ];
    }

    public function map($payment): array
    {
        return [
            $payment->paid_at?->format('d/m/Y'),
            $payment->lease?->client?->full_name ?? 'N/A',
            $payment->period_start?->format('m/Y') ?? 'N/A',
            $payment->period_end?->format('m/Y') ?? 'N/A',
            $payment->lease?->unit?->property?->name ?? 'N/A',
            $payment->lease?->unit?->name ?? 'N/A',
            $payment->amount,
            ucfirst($payment->method ?? 'Espèces'),
            $payment->status?->label() ?? $payment->status,
            $payment->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
