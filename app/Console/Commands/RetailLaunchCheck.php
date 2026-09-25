<?php

namespace App\Console\Commands;

use App\Models\ContentPage;
use App\Models\Product;
use App\Models\StandardShippingRate;
use App\Services\PayPalService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('retail:launch-check')]
#[Description('Check whether the Leather Wallets retail storefront is ready for production launch')]
class RetailLaunchCheck extends Command
{
    public function handle(PayPalService $paypal): int
    {
        $checks = [
            'Production environment' => config('app.env') === 'production',
            'Debug mode disabled' => ! config('app.debug'),
            'HTTPS application URL' => str_starts_with((string) config('app.url'), 'https://'),
            'Wholesale domain configured' => config('storefronts.wholesale.domain') === 'igicanada.ca',
            'Retail domain configured' => config('storefronts.retail.domain') === 'leatherwallets.ca',
            'Admin restricted to IGI Canada' => config('storefronts.admin_domain') === 'igicanada.ca',
            'Live PayPal credentials configured' => config('services.paypal.mode') === 'live' && $paypal->configured(),
            'Tax obligations reviewed' => (bool) config('retail-tax.reviewed'),
            'GST/HST registration number present when collection is enabled' => ! config('retail-tax.gst_hst_registered') || filled(config('retail-tax.registration_number')),
            'Shipping charges approved by the business' => (bool) config('commerce.shipping_rates_reviewed'),
            'Shipping slabs cover CA and US without gaps' => $this->shippingSlabsAreComplete(),
            'Retail legal content approved by the business' => (bool) config('retail-legal.reviewed'),
            'Retail legal pages finalized and published' => $this->legalPagesAreFinalized(),
            'At least one sellable retail product' => Product::query()
                ->where('is_active', true)
                ->whereIn('visibility', ['retail', 'both'])
                ->whereHas('variants', fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_available_retail', true)
                    ->whereNotNull('retail_price')
                    ->where('stock_quantity', '>', 0))
                ->exists(),
        ];

        $this->table(['Check', 'Status'], collect($checks)->map(
            fn (bool $passed, string $check): array => [$check, $passed ? 'PASS' : 'ACTION REQUIRED'],
        )->values()->all());

        if (in_array(false, $checks, true)) {
            $this->warn('Retail storefront is not ready for live traffic. Complete every action-required item and run this check again.');

            return self::FAILURE;
        }

        $this->info('Leather Wallets storefront passed all application launch checks. Verify DNS, TLS, queues, email delivery, and a live low-value payment before opening traffic.');

        return self::SUCCESS;
    }

    private function shippingSlabsAreComplete(): bool
    {
        foreach (['wholesale', 'retail', 'walletsandbelts'] as $channel) {
            foreach (['CA', 'US'] as $country) {
                $rates = StandardShippingRate::query()
                    ->where('sales_channel', $channel)
                    ->where('country', $country)
                    ->where('is_active', true)
                    ->orderBy('min_order_amount')
                    ->get(['min_order_amount', 'max_order_amount']);

                if ($rates->isEmpty() || (float) $rates->first()->min_order_amount !== 0.0) {
                    return false;
                }

                $previousMaximum = null;
                foreach ($rates as $rate) {
                    $minimum = round((float) $rate->min_order_amount, 2);
                    $maximum = round((float) $rate->max_order_amount, 2);

                    if ($maximum < $minimum || ($previousMaximum !== null && $minimum !== round($previousMaximum + 0.01, 2))) {
                        return false;
                    }

                    $previousMaximum = $maximum;
                }

                if ($previousMaximum < 10000) {
                    return false;
                }
            }
        }

        return true;
    }

    private function legalPagesAreFinalized(): bool
    {
        $pages = ContentPage::forChannel('retail')
            ->where('is_legal', true)
            ->whereIn('slug', ['privacy-policy', 'terms-of-sale', 'shipping-returns'])
            ->get(['slug', 'body_html', 'status', 'published_at']);

        if ($pages->count() !== 3) {
            return false;
        }

        return $pages->every(function (ContentPage $page): bool {
            $body = strtolower((string) $page->body_html);

            return $page->status === 'published'
                && ($page->published_at === null || $page->published_at->isPast())
                && ! str_contains($body, 'review required')
                && ! str_contains($body, 'before publishing');
        });
    }
}
