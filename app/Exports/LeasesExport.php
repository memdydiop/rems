<?php

namespace App\Exports;

use App\Models\Lease;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeasesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Lease::with(['client', 'unit.property'])
            ->latest('start_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Client',
            'Propriété',
            'Unité',
            'Loyer (FCFA)',
            'Charges (FCFA)',
            'Caution (FCFA)',
            'Avance (FCFA)',
            'Début',
            'Fin',
            'Type',
            'Statut',
        ];
    }

    public function map($lease): array
    {
        return [
            $lease->client?->full_name ?? 'N/A',
            $lease->unit?->property?->name ?? 'N/A',
            $lease->unit?->name ?? 'N/A',
            $lease->rent_amount,
            $lease->charges_amount,
            $lease->deposit_amount,
            $lease->advance_amount,
            $lease->start_date?->format('d/m/Y'),
            $lease->end_date?->format('d/m/Y') ?? 'Indéterminé',
            $lease->lease_type?->label() ?? 'N/A',
            $lease->status?->label() ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
