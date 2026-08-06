<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select store · IGI Canada Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f7f7f8; color: #18181b; font-family: ui-sans-serif, system-ui, sans-serif; }
        main { width: min(900px, 100%); }
        .eyebrow { color: #b91c1c; font-size: 13px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 10px 0 8px; font-size: clamp(30px, 5vw, 46px); letter-spacing: -.04em; }
        .intro { margin: 0 0 30px; color: #52525b; font-size: 17px; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        form { margin: 0; }
        button { width: 100%; min-height: 190px; padding: 24px; border: 1px solid #e4e4e7; border-radius: 18px; background: white; color: inherit; cursor: pointer; text-align: left; box-shadow: 0 8px 30px rgba(0,0,0,.05); transition: transform .15s, border-color .15s, box-shadow .15s; }
        button:hover, button:focus-visible { transform: translateY(-3px); border-color: #dc2626; box-shadow: 0 14px 35px rgba(185,28,28,.12); outline: none; }
        .icon { display: grid; width: 44px; height: 44px; place-items: center; margin-bottom: 28px; border-radius: 12px; background: #fef2f2; color: #b91c1c; font-weight: 800; }
        strong { display: block; margin-bottom: 8px; font-size: 19px; }
        small { color: #71717a; font-size: 14px; line-height: 1.5; }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } button { min-height: 150px; } }
    </style>
</head>
<body>
<main>
    <div class="eyebrow">IGI Canada Admin</div>
    <h1>Which store would you like to manage?</h1>
    <p class="intro">Your choice filters the dashboard and all shared commerce records. You can switch stores anytime from the admin header.</p>

    <div class="grid">
        @foreach ($options as $value => $label)
            <form method="POST" action="{{ route('admin.storefront.store') }}">
                @csrf
                <input type="hidden" name="sales_channel" value="{{ $value }}">
                <input type="hidden" name="initial_selection" value="1">
                <button type="submit">
                    <span class="icon">{{ $value === 'all' ? 'A' : ($value === 'retail' ? 'R' : 'W') }}</span>
                    <strong>{{ $label }}</strong>
                    <small>{{ match ($value) { 'retail' => 'Retail orders, products, customers, enquiries and content.', 'wholesale' => 'Wholesale orders, accounts, enquiries, products and content.', default => 'Combined overview of both websites and all activity.' } }}</small>
                </button>
            </form>
        @endforeach
    </div>
</main>
</body>
</html>
