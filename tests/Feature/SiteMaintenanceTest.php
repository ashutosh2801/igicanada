<?php

namespace Tests\Feature;

use App\Models\HomepageSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);
    }

    public function test_unpublished_wholesale_site_serves_a_503_maintenance_page(): void
    {
        HomepageSetting::query()->forChannel('wholesale')->firstOrFail()->update(['is_active' => false]);

        $this->get('https://igicanada.ca/')
            ->assertStatus(503)
            ->assertSee('taking a short break')
            ->assertSee('IGI Canada');
    }

    public function test_unpublish_respects_the_domain_based_settings_channel(): void
    {
        HomepageSetting::query()->forChannel('retail')->firstOrFail()->update(['is_active' => false]);

        $this->get('https://leatherwallets.ca/')->assertStatus(503);
        $this->get('https://walletsandbelts.com/')->assertSuccessful();

        HomepageSetting::query()->forChannel('retail')->firstOrFail()->update(['is_active' => true]);
        HomepageSetting::query()->forChannel('walletsandbelts')->firstOrFail()->update(['is_active' => false]);

        $this->get('https://walletsandbelts.com/')->assertStatus(503)->assertSee('Wallets and Belts');
        $this->get('https://leatherwallets.ca/')->assertSuccessful();
    }

    public function test_unpublishing_does_not_block_the_admin_or_paypal_webhook(): void
    {
        HomepageSetting::query()->forChannel('wholesale')->firstOrFail()->update(['is_active' => false]);

        $this->actingAs($this->admin(), 'admin');
        $adminResponse = $this->get('https://igicanada.ca/admin');
        $this->assertNotSame(503, $adminResponse->getStatusCode());

        $webhookResponse = $this->postJson('https://igicanada.ca/payments/paypal/webhook', []);
        $this->assertNotSame(503, $webhookResponse->getStatusCode());
    }

    public function test_publishing_the_site_brings_it_back(): void
    {
        $settings = HomepageSetting::query()->forChannel('retail')->firstOrFail();
        $settings->update(['is_active' => false]);
        $this->get('https://leatherwallets.ca/')->assertStatus(503);

        $settings->update(['is_active' => true]);
        $this->get('https://leatherwallets.ca/')->assertSuccessful();
    }

    public function test_backend_and_admin_assets_keep_working_while_unpublished(): void
    {
        HomepageSetting::query()->forChannel('wholesale')->firstOrFail()->update(['is_active' => false]);

        $livewirePath = parse_url(route('default-livewire.update'), PHP_URL_PATH);
        $this->assertStringStartsWith('/livewire-', $livewirePath);
        $this->assertNotSame(503, $this->postJson($livewirePath, [])->getStatusCode());
        $this->assertNotSame(
            503,
            $this->get(str_replace('/update', '/livewire.js', $livewirePath))->getStatusCode(),
        );

        $this->assertNotSame(503, $this->get('/css/filament/filament/app.css')->getStatusCode());
        $this->assertNotSame(503, $this->get('/js/filament/filament/app.js')->getStatusCode());

        $this->assertNotSame(
            503,
            $this->getJson(route('filament.exports.download', ['export' => 1]))->getStatusCode(),
        );
    }
}