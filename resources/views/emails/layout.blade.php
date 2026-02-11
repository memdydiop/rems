<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #18181b;
            margin: 0;
            padding: 0;
            background-color: #f4f4f5;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #3b82f6;
        }

        .content h1 {
            font-size: 24px;
            color: #18181b;
            margin: 0 0 20px 0;
        }

        .content p {
            color: #52525b;
            margin: 0 0 15px 0;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
        }

        .button:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
        }

        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }

        .info-box p {
            margin: 0;
            color: #0369a1;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #a1a1aa;
            font-size: 14px;
        }

        .footer a {
            color: #71717a;
        }

        .divider {
            border-top: 1px solid #e4e4e7;
            margin: 30px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">{{ config('app.name') }}</div>
            </div>

            <div class="content">
                {{ $slot }}
            </div>

            <div class="divider"></div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
                <p>
                    <a href="{{ config('app.url') }}">Visiter notre site</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>