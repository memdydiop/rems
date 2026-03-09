<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Compte Rendu de Gestion</title>
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

        .report-info {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }

        .report-month {
            background: #4f46e5;
            color: white;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: bold;
        }

        .owner-details {
            margin-top: 10px;
            font-size: 11px;
            color: #555;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 5px;
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #f9fafb;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
            font-size: 11px;
            color: #666;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .summary-box {
            background: #f0fdf4;
            border: 2px solid #10b981;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .summary-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 28px;
            font-weight: bold;
            color: #10b981;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <h1>PMS</h1>
            <p>Agence Immobilière</p>
        </div>
        <div class="report-info">
            <div class="report-month">COMPTE RENDU : {{ $monthName }} {{ $year }}</div>
            <div class="owner-details">
                Propr. : {{ $owner->first_name }} {{ $owner->last_name }}<br>
                {{ $owner->email }}
            </div>
        </div>
    </div>

    <div class="summary-box">
        <div class="summary-label">Total Net à Reverser</div>
        <div class="summary-value">{{ number_format($totalNet, 0, ',', ' ') }} XOF</div>
    </div>

    <div class="section-title">Détail par Propriété</div>

    <table>
        <thead>
            <tr>
                <th width="40%">Propriété / Unité</th>
                <th width="20%">Client</th>
                <th width="20%" class="text-right">Revenus</th>
                <th width="20%" class="text-right">Commissions ({{ $commissionRate }}%)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $detail)
                <tr>
                    <td>
                        <div class="font-bold">{{ $detail['property_name'] }}</div>
                        <div style="font-size: 10px; color: #666;">{{ $detail['unit_name'] }}</div>
                        <div style="font-size: 10px; color: #999;">{{ $detail['address'] }}</div>
                    </td>
                    <td>{{ $detail['client_name'] }}</td>
                    <td class="text-right">{{ number_format($detail['rent_collected'], 0, ',', ' ') }}</td>
                    <td class="text-right text-red-600">- {{ number_format($detail['commission'], 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #fafafa;">
                <td colspan="2" class="font-bold text-right">TOTAUX</td>
                <td class="font-bold text-right">{{ number_format($totalCollected, 0, ',', ' ') }}</td>
                <td class="font-bold text-right">- {{ number_format($totalCommission, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
        <p>Merci de votre confiance.</p>
    </div>
</body>

</html>