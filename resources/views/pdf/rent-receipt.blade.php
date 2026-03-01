<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #1a1a2e;
            font-size: 13px;
            line-height: 1.6;
        }

        .page {
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            color: #4f46e5;
            letter-spacing: -0.5px;
        }

        .header-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .ref {
            font-size: 11px;
            color: #6b7280;
            text-align: right;
        }

        .ref strong {
            color: #1a1a2e;
        }

        .parties {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .party-col {
            display: table-cell;
            width: 48%;
            vertical-align: top;
        }

        .party-spacer {
            display: table-cell;
            width: 4%;
        }

        .party-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
        }

        .party-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .party-name {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .party-detail {
            font-size: 12px;
            color: #4b5563;
            margin-top: 2px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .details-table th {
            background: #4f46e5;
            color: #fff;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 16px;
            text-align: left;
        }

        .details-table th:first-child {
            border-radius: 8px 0 0 0;
        }

        .details-table th:last-child {
            border-radius: 0 8px 0 0;
            text-align: right;
        }

        .details-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .details-table td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .details-table .total-row td {
            background: #f0fdf4;
            font-weight: 700;
            font-size: 15px;
            border-top: 2px solid #4f46e5;
        }

        .attestation {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .attestation-title {
            font-weight: 700;
            color: #0369a1;
            margin-bottom: 8px;
        }

        .attestation p {
            font-size: 12px;
            color: #374151;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
        }

        .signature-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #d1d5db;
            width: 200px;
            display: inline-block;
        }

        .signature-name {
            font-size: 12px;
            color: #4b5563;
            margin-top: 4px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
    </style>
</head>

<body>
    <div class="page">
        <!-- Header -->
        <table style="width: 100%; margin-bottom: 30px; border-bottom: 3px solid #4f46e5; padding-bottom: 25px;">
            <tr>
                <td style="vertical-align: top;">
                    <div class="header-title">QUITTANCE DE LOYER</div>
                    <div class="header-subtitle">Document officiel de quittance</div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div class="ref">
                        <strong>N° {{ strtoupper(substr($payment->id, 0, 8)) }}</strong><br>
                        Date : {{ now()->format('d/m/Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Parties -->
        <table class="parties">
            <tr>
                <td class="party-col">
                    <div class="party-box">
                        <div class="party-label">Bailleur</div>
                        <div class="party-name">{{ $property->name }}</div>
                        <div class="party-detail">{{ $property->address }}</div>
                    </div>
                </td>
                <td class="party-spacer"></td>
                <td class="party-col">
                    <div class="party-box">
                        <div class="party-label">Locataire</div>
                        <div class="party-name">{{ $renter->first_name }} {{ $renter->last_name }}</div>
                        <div class="party-detail">{{ $renter->email }}</div>
                        @if($renter->phone)
                            <div class="party-detail">{{ $renter->phone }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Payment Details -->
        <table class="details-table">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Détail</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Loyer mensuel</td>
                    <td>{{ $unit->name }} — {{ $property->name }}</td>
                    <td>{{ number_format($payment->amount, 0, ',', ' ') }} XOF</td>
                </tr>
                <tr>
                    <td>Période</td>
                    <td colspan="2">{{ \Carbon\Carbon::parse($payment->paid_at)->translatedFormat('F Y') }}</td>
                </tr>
                <tr>
                    <td>Mode de paiement</td>
                    <td colspan="2">{{ ucfirst($payment->method ?? 'Espèces') }}</td>
                </tr>
                <tr>
                    <td>Date du paiement</td>
                    <td colspan="2">{{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2">TOTAL REÇU</td>
                    <td>{{ number_format($payment->amount, 0, ',', ' ') }} XOF</td>
                </tr>
            </tbody>
        </table>

        <!-- Attestation -->
        <div class="attestation">
            <div class="attestation-title">Attestation de paiement</div>
            <p>
                Je soussigné(e), atteste avoir reçu de <strong>{{ $renter->first_name }}
                    {{ $renter->last_name }}</strong>
                la somme de <strong>{{ number_format($payment->amount, 0, ',', ' ') }} XOF</strong>
                au titre du loyer de l'unité <strong>{{ $unit->name }}</strong>
                située à <strong>{{ $property->address }}</strong>
                pour la période de
                <strong>{{ \Carbon\Carbon::parse($payment->paid_at)->translatedFormat('F Y') }}</strong>.
            </p>
        </div>

        <!-- Signature -->
        <div class="signature">
            <div class="signature-label">Le Bailleur</div>
            <div class="signature-line"></div>
            <div class="signature-name">Fait le {{ now()->format('d/m/Y') }}</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Ce document est une quittance de loyer délivrée conformément à l'article 21 de la loi n° 89-462 du 6 juillet
            1989.
            <br>Document généré automatiquement — PMS
        </div>
    </div>
</body>

</html>