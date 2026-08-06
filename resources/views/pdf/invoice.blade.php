<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $order->order_number }}</title>
    <style>
        @page { margin: 42px 44px 56px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000000; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.45; }
        .header { width: 100%; border-bottom: 2px solid #d80621; padding-bottom: 18px; }
        .header td { vertical-align: top; }
        .brand { font-size: 24px; font-weight: bold; letter-spacing: -1px; }
        .brand span, .document-title, .label { color: #d80621; }
        .document-title { font-size: 18px; font-weight: bold; text-align: right; }
        .muted { color: #000000; opacity: .65; }
        .right { text-align: right; }
        .meta { width: 100%; margin-top: 24px; }
        .meta td { width: 50%; vertical-align: top; }
        .label { margin-bottom: 5px; font-size: 8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .address { line-height: 1.6; }
        table.items { width: 100%; margin-top: 26px; border-collapse: collapse; }
        .items th { padding: 9px 7px; background: #000000; color: white; font-size: 8px; letter-spacing: .6px; text-align: left; text-transform: uppercase; }
        .items td { padding: 10px 7px; border-bottom: 1px solid #000000; vertical-align: top; }
        .items .number { text-align: right; white-space: nowrap; }
        .summary { width: 280px; margin: 20px 0 0 auto; border-collapse: collapse; }
        .summary td { padding: 5px 7px; }
        .summary .total td { padding-top: 10px; border-top: 2px solid #000000; font-size: 13px; font-weight: bold; }
        .notice { margin-top: 24px; padding: 12px 14px; border-left: 4px solid #d80621; background: #ffffff; color: #000000; }
        .notes { margin-top: 18px; padding-top: 14px; border-top: 1px solid #000000; }
        .footer { position: fixed; right: 0; bottom: -35px; left: 0; color: #000000; font-size: 8px; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">{{ $company['name'] }} · {{ $company['phone'] }} · {{ $company['email'] }} · Page <span class="page-number"></span></div>
    <table class="header">
        <tr>
            <td>
                <div class="brand">IGI <span>CANADA</span></div>
                <div class="muted">{{ $company['address'] }}<br>{{ $company['city'] }}, {{ $company['country'] }}<br>{{ $company['phone'] }} · {{ $company['email'] }}</div>
            </td>
            <td class="right">
                <div class="document-title">{{ $order->legacy_id ? 'HISTORICAL ORDER' : ($order->status === 'awaiting_quote' ? 'ORDER SUMMARY' : 'INVOICE') }}</div>
                <strong>{{ $order->order_number }}</strong><br>
                <span class="muted">{{ $order->placed_at?->format('F j, Y') }}</span><br>
                <span class="muted">Status: {{ str($order->status)->replace('_', ' ')->title() }}</span>
            </td>
        </tr>
    </table>
    <table class="meta">
        <tr>
            <td>
                <div class="label">Bill to</div>
                <strong>{{ $order->user?->name ?: data_get($order->shipping_address, 'name') }}</strong><br>
                @if($order->user?->resellerProfile?->company){{ $order->user->resellerProfile->company }}<br>@endif
                {{ $order->user?->email ?: $order->customer_email }}
            </td>
            <td>
                <div class="label">Ship to</div>
                <div class="address">
                    <strong>{{ data_get($order->shipping_address, 'name') }}</strong><br>
                    @if(data_get($order->shipping_address, 'company')){{ data_get($order->shipping_address, 'company') }}<br>@endif
                    {{ data_get($order->shipping_address, 'address') }}<br>
                    {{ data_get($order->shipping_address, 'city') }}, {{ data_get($order->shipping_address, 'province') }} {{ data_get($order->shipping_address, 'postal_code') }}<br>
                    {{ data_get($order->shipping_address, 'country') }}
                </div>
            </td>
        </tr>
    </table>
    <table class="items">
        <thead><tr><th>Product</th><th>SKU / option</th><th class="number">Qty</th><th class="number">Unit</th><th class="number">Amount</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td><strong>{{ $item->product_name }}</strong></td>
                    <td class="muted">{{ $item->sku ?: '-' }}@if($item->option)<br>{{ $item->option }}@endif</td>
                    <td class="number">{{ $item->quantity }}</td>
                    <td class="number">&#36;{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="number">&#36;{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="summary">
        <tr><td class="muted">Products</td><td class="right">&#36;{{ number_format((float) $order->subtotal, 2) }}</td></tr>
        @if((float) $order->discount_total > 0)<tr><td class="muted">Discount</td><td class="right">-&#36;{{ number_format((float) $order->discount_total, 2) }}</td></tr>@endif
        <tr><td class="muted">Shipping</td><td class="right">&#36;{{ number_format((float) $order->shipping_total, 2) }}</td></tr>
        @if($order->shipping_method || $order->shipping_service)<tr><td colspan="2" class="muted right">{{ collect([$order->shipping_method, $order->shipping_service])->filter()->join(' · ') }}</td></tr>@endif
        <tr><td class="muted">{{ data_get($order->tax_breakdown, 'label', 'Tax') }}@if(data_get($order->tax_breakdown, 'rate')) ({{ number_format((float) data_get($order->tax_breakdown, 'rate') * 100, 3) }}%)@endif</td><td class="right">&#36;{{ number_format((float) $order->tax_total, 2) }}</td></tr>
        <tr class="total"><td>{{ $order->legacy_id ? 'Recorded total' : 'Total' }}</td><td class="right">&#36;{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td></tr>
    </table>
    @if($order->legacy_id)
        <div class="notice">Historical record migrated from the previous system. Values are shown as originally stored; some older records may have incomplete shipping, tax or line-total information.</div>
    @elseif($order->status === 'awaiting_quote')
        <div class="notice">This is an order request, not a final invoice. The selected Canada Post shipping rate is included; applicable taxes will be confirmed by IGI Canada before payment.</div>
    @endif
    @if($order->customer_notes)<div class="notes"><div class="label">Customer notes</div>{{ $order->customer_notes }}</div>@endif
</body>
</html>
