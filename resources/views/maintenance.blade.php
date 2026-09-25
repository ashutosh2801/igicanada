<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $metaTitle ?? $brandName ?? 'We\'ll be back soon' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f4f1ec;
            color: #2a2620;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding: 24px;
        }
        .maintenance {
            max-width: 520px;
            text-align: center;
        }
        .maintenance__brand {
            font-size: 14px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #8a8178;
            margin: 0 0 16px;
        }
        .maintenance h1 {
            font-size: 32px;
            line-height: 1.2;
            margin: 0 0 12px;
        }
        .maintenance p {
            font-size: 17px;
            line-height: 1.6;
            color: #6b645b;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="maintenance">
        <p class="maintenance__brand">{{ $brandName ?? 'Our store' }}</p>
        <h1>We're taking a short break</h1>
        <p>Our online store is temporarily closed for the holidays and maintenance. We'll be back soon — thank you for your patience.</p>
    </div>
</body>
</html>