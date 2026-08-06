<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $siteSetting = \Illuminate\Support\Facades\Schema::hasTable('homepage_settings')
            && \Illuminate\Support\Facades\Schema::hasColumn('homepage_settings', 'sales_channel')
            ? \App\Models\HomepageSetting::query()->forChannel('retail')->first()
            : null;
        $favicon = \App\Support\StorefrontAsset::uploaded($siteSetting?->favicon_path);
        $ogImage = \App\Support\StorefrontAsset::uploaded($siteSetting?->og_image_path);
        $twitterImage = \App\Support\StorefrontAsset::uploaded($siteSetting?->twitter_image_path);
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="canonical" href="{{ request()->url() }}">
        <meta name="description" content="{{ $siteSetting?->default_meta_description ?? 'Leather wallets and accessories, thoughtfully made for everyday carry.' }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $siteSetting?->og_title ?? $siteSetting?->default_meta_title ?? 'Leather Wallets Canada' }}">
        @if($siteSetting?->og_description)<meta property="og:description" content="{{ $siteSetting->og_description }}">@endif
        @if($ogImage)<meta property="og:image" content="{{ url($ogImage) }}">@endif
        <meta name="twitter:card" content="{{ $siteSetting?->twitter_card ?? 'summary_large_image' }}">
        <meta name="twitter:title" content="{{ $siteSetting?->twitter_title ?? $siteSetting?->og_title ?? 'Leather Wallets Canada' }}">
        @if($siteSetting?->twitter_description)<meta name="twitter:description" content="{{ $siteSetting->twitter_description }}">@endif
        @if($twitterImage)<meta name="twitter:image" content="{{ url($twitterImage) }}">@endif
        @if($favicon)<link rel="icon" href="{{ $favicon }}">@endif
        <title inertia>{{ $siteSetting?->default_meta_title ?? 'Leather Wallets Canada' }}</title>
        @viteReactRefresh
        @vite(['resources/css/retail.css', 'resources/js/retail/app.tsx'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
