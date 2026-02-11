<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }

        .logo {
            display: table-cell;
            width: 60%;
        }

        .logo h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 28px;
        }

        .logo p {
            color: #666;
            margin: 5px 0 0;
        }

        .receipt-info {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }

        .receipt-number {
            background: #4f46e5;
            color: white;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .success-badge {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            display: inline-block;
            margin-top: 10px;
            font-weight: bold;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 5px;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 35%;
            padding: 8px 10px;
            font-weight: bold;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .info-value {
            display: table-cell;
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
        }

        .amount-box {
            background: #f0fdf4;
            border: 2px solid #10b981;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }

        .amount-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .amount-value {
            font-size: 32px;
            font-weight: bold;
            color: #10b981;
        }

        .amount-currency {
            font-size: 16px;
            color: #666;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }

        .footer p {
            margin: 3px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <h1>PMS</h1>
            <p>Property Management System</p>
        </div>
        <div class="receipt-info">
            <div class="receipt-number">REÇU #{{ strtoupper(substr($payment->id, 0, 8)) }}</div>
            <div class="success-badge">✓ PAYÉ</div>
        </div>
    </div>

    <div class="amount-box">
        <div class="amount-label">Montant payé</div>
        <div class="amount-value">{{ number_format($payment->amount, 0, ',', ' ') }} <span
                class="amount-currency">XOF</span></div>
    </div>

    <div class="section">
        <div class="section-title">Détails du Paiement</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date de paiement</div>
                <div class="info-value">{{ $payment->paid_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Mode de paiement</div>
                <div class="info-value">{{ ucfirst($payment->method ?? 'Espèces') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Période concernée</div>
                <div class="info-value">{{ now()->format('F Y') }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Locataire</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet</div>
                <div class="info-value">{{ $renter->first_name }} {{ $renter->last_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $renter->email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Téléphone</div>
                <div class="info-value">{{ $renter->phone ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Bien Loué</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Propriété</div>
                <div class="info-value">{{ $property->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Unité</div>
                <div class="info-value">{{ $unit->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Adresse</div>
                <div class="info-value">{{ $property->address ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Ce document fait foi de reçu de paiement.</p>
        <p>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>PMS - Property Management System</p>
    </div>
</body>

</html>