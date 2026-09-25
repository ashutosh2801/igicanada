<?php

namespace Tests\Feature;

use App\Models\StandardShippingRate;
use App\Services\StandardShippingService;
use App\Support\StorefrontContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ShippingChargesPerSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_quote_uses_rates_configured_for_each_site(): void
    {
        StandardShippingRate::query()
            ->where('sales_channel', 'walletsandbelts')
            ->where('country', 'CA')
            ->where('min_order_amount', 0)
            ->update(['charge' => 29.95]);

        StandardShippingRate::query()
            ->where('sales_channel', 'retail')
            ->where('country', 'CA')
            ->where('min_order_amount', 0)
            ->update(['charge' => 18.95]);

        StandardShippingRate::query()
            ->where('sales_channel', 'wholesale')
            ->where('country', 'CA')
            ->where('min_order_amount', 0)
            ->update(['charge' => 21.95]);

        $shipping = app(StandardShippingService::class);

        $this->assertSame('18.95', $shipping->quote(99.99, 'Canada', 'CA', 'retail')['price']);
        $this->assertSame('29.95', $shipping->quote(99.99, 'Canada', 'CA', 'walletsandbelts')['price']);
        $this->assertSame('21.95', $shipping->quote(99.99, 'Canada', 'CA', 'wholesale')['price']);
    }

    public function test_each_retail_domain_resolves_to_its_own_shipping_channel(): void
    {
        $walletsAndBelts = StorefrontContext::fromRequest(Request::create('https://walletsandbelts.com/checkout'));
        $this->assertSame('walletsandbelts', $walletsAndBelts->settingsChannel());

        $leatherWallets = StorefrontContext::fromRequest(Request::create('https://leatherwallets.ca/checkout'));
        $this->assertSame('retail', $leatherWallets->settingsChannel());
    }
}