<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return Client::latest()->get();
    }

    public function headings(): array
    {
        return [
            'Prénom',
            'Nom',
            'Email',
            'Téléphone',
            'Statut',
            'Date d\'inscription',
        ];
    }

    public function map($client): array
    {
        return [
            $client->first_name,
            $client->last_name,
            $client->email ?? 'Non fourni',
            $client->phone ?? 'Non fourni',
            $client->status?->label() ?? 'N/A',
            $client->created_at?->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
