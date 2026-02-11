<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Contrat de Bail</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 24px;
        }

        .header p {
            color: #666;
            margin: 5px 0 0;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            background: #f3f4f6;
            padding: 8px 12px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            border-left: 4px solid #4f46e5;
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
            width: 40%;
            padding: 5px 10px;
            font-weight: bold;
            background: #f9fafb;
        }

        .info-value {
            display: table-cell;
            padding: 5px 10px;
        }

        .signatures {
            margin-top: 60px;
            page-break-inside: avoid;
        }

        .signature-row {
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 45%;
            border: 1px solid #ddd;
            padding: 20px;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 60px;
            margin-bottom: 10px;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>CONTRAT DE BAIL</h1>
        <p>Bail de location résidentielle</p>
    </div>

    <div class="section">
        <div class="section-title">PARTIES CONCERNÉES</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Bailleur (Propriétaire)</div>
                <div class="info-value">{{ $property->owner_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Adresse du bailleur</div>
                <div class="info-value">{{ $property->address ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Locataire</div>
                <div class="info-value">{{ $lease->renter->first_name }} {{ $lease->renter->last_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email du locataire</div>
                <div class="info-value">{{ $lease->renter->email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Téléphone du locataire</div>
                <div class="info-value">{{ $lease->renter->phone ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">BIEN LOUÉ</div>
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
                <div class="info-value">{{ $property->address }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">CONDITIONS DU BAIL</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date de début</div>
                <div class="info-value">{{ $lease->start_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de fin</div>
                <div class="info-value">{{ $lease->end_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Loyer mensuel</div>
                <div class="info-value">{{ number_format($lease->rent_amount, 0, ',', ' ') }} XOF</div>
            </div>
            <div class="info-row">
                <div class="info-label">Dépôt de garantie</div>
                <div class="info-value">{{ number_format($lease->deposit_amount, 0, ',', ' ') }} XOF</div>
            </div>
        </div>
    </div>

    <div class="signatures">
        <div class="signature-row">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p><strong>Le Bailleur</strong></p>
                <p>{{ $property->owner_name ?? 'Propriétaire' }}</p>
            </div>
            <div style="display: table-cell; width: 10%;"></div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <p><strong>Le Locataire</strong></p>
                <p>{{ $lease->renter->first_name }} {{ $lease->renter->last_name }}</p>
            </div>
        </div>
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} | PMS - Property Management System
    </div>
</body>

</html>