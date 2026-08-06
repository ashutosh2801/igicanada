<?php

namespace Tests\Feature;

use App\Models\ContentPage;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetailLaunchCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_check_passes_only_when_required_configuration_and_content_are_ready(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        config()->set('app.url', 'https://igicanada.ca');
        config()->set('storefronts.admin_domain', 'igicanada.ca');
        config()->set('services.paypal', [
            'mode' => 'live',
            'client_id' => 'live-client',
            'client_secret' => 'live-secret',
            'webhook_id' => 'live-webhook',
        ]);
        config()->set('retail-tax.reviewed', true);
        config()->set('commerce.shipping_rates_reviewed', true);
        config()->set('retail-legal.reviewed', true);

        ContentPage::forChannel('retail')->where('is_legal', true)->update([
            'status' => 'published',
            'published_at' => now(),
            'body_html' => '<h2>Approved policy</h2><p>Final business-approved content.</p>',
        ]);
        $product = Product::create([
            'name' => 'Retail Wallet',
            'slug' => 'retail-wallet',
            'visibility' => 'retail',
            'is_active' => true,
        ]);
        $product->variants()->create([
            'retail_price' => 49,
            'stock_quantity' => 5,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        $this->artisan('retail:launch-check')
            ->expectsOutputToContain('passed all application launch checks')
            ->assertSuccessful();
    }
}
