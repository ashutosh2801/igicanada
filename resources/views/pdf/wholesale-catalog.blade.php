<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>IGI Canada Wholesale Catalog</title>
    <style>
        @page { margin: 38px 38px 48px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #171717; font-family: DejaVu Sans, sans-serif; font-size: 9px; line-height: 1.4; }
        .header { border-bottom: 3px solid #d80621; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { font-size: 25px; font-weight: bold; letter-spacing: -1px; }
        .brand span, .accent { color: #d80621; }
        .date { color: #555; font-size: 10px; margin-top: 4px; }
        .intro { color: #555; margin: 0 0 18px; }
        .product { page-break-inside: avoid; border-bottom: 1px solid #d5d5d5; padding: 0 0 14px; margin: 0 0 16px; }
        .product-table { width: 100%; border-collapse: collapse; }
        .product-table td { vertical-align: top; }
        .images { width: 168px; padding-right: 14px; }
        .image { display: inline-block; width: 74px; height: 74px; margin-right: 5px; border: 1px solid #ddd; padding: 3px; text-align: center; vertical-align: top; }
        .image img { width: 100%; height: 100%; object-fit: contain; }
        .no-image { color: #aaa; padding-top: 25px; font-size: 8px; }
        .name { font-size: 13px; font-weight: bold; }
        .sku { color: #666; font-size: 8px; margin: 2px 0 6px; }
        .description { color: #555; max-height: 40px; overflow: hidden; }
        .variant { margin-top: 7px; border-top: 1px solid #eee; padding-top: 5px; }
        .variant strong { color: #222; }
        .price { color: #d80621; font-weight: bold; }
        .footer { position: fixed; right: 0; bottom: -28px; left: 0; color: #666; font-size: 8px; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">{{ $company['name'] }} · {{ $company['email'] }} · Page <span class="page-number"></span></div>
    <div class="header">
        <div class="brand">IGI <span>CANADA</span></div>
        <div class="date">Wholesale product catalog · {{ $catalogDate->format('F j, Y') }}</div>
    </div>
    <p class="intro">Product information and wholesale availability current as of {{ $catalogDate->format('F j, Y') }}. Please contact IGI Canada to confirm final pricing and availability.</p>
    @foreach($products as $entry)
        @php($product = $entry['product'])
        <div class="product">
            <table class="product-table"><tr>
                <td class="images">
                    @forelse($entry['images'] as $image)
                        <span class="image"><img src="{{ $image }}" alt=""></span>
                    @empty
                        <span class="image no-image">Image unavailable</span>
                    @endforelse
                </td>
                <td>
                    <div class="name">{{ $product->name }}</div>
                    @if($product->sku)<div class="sku">SKU: {{ $product->sku }}</div>@endif
                    @if($product->description)<div class="description">{{ str(strip_tags($product->description))->squish()->limit(240) }}</div>@endif
                    @foreach($product->variants as $variant)
                        <div class="variant">
                            <strong>{{ $variant->color ?: 'Standard' }}</strong>
                            @if($variant->sizeLabels()) · Sizes: {{ implode(', ', $variant->sizeLabels()) }} @endif
                            @if($showPrices && $variant->wholesale_price !== null) · <span class="price">${{ number_format((float) $variant->wholesale_price, 2) }} CAD</span>@endif
                            @if($showStock) · Stock: {{ max(0, (int) $variant->stock_quantity) }}@endif
                        </div>
                    @endforeach
                </td>
            </tr></table>
        </div>
    @endforeach
</body>
</html>
