<?php

namespace App\Exports;

use App\Models\Property;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PropertiesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Property::with(['owner', 'units'])->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Nom',
            'Adresse',
            'Type',
            'Statut',
            'Propriétaire',
            'Nb Unités',
            'Transaction',
        ];
    }

    public function map($property): array
    {
        return [
            $property->name,
            $property->address ?? 'Non renseignée',
            $property->type?->label() ?? 'N/A',
            $property->status?->label() ?? 'N/A',
            $property->owner?->name ?? 'Non assigné',
            $property->units->count(),
            $property->transaction_type?->label() ?? 'N/A',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
