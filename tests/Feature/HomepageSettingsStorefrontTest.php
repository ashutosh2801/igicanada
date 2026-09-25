<?php

namespace Tests\Feature;

use App\Filament\Resources\HomepageSettings\Pages\EditHomepageSetting;
use App\Filament\Resources\HomepageSettings\Pages\ListHomepageSettings;
use App\Models\HomepageSetting;
use App\Models\User;
use App\Support\AdminStorefront;
use App\Support\StorefrontContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class HomepageSettingsStorefrontTest extends TestCase
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

    public function test_settings_channel_is_resolved_from_the_served_domain(): void
    {
        $this->assertSame('retail', StorefrontContext::fromRequest(Request::create('https://leatherwallets.ca/'))->settingsChannel());
        $this->assertSame('walletsandbelts', StorefrontContext::fromRequest(Request::create('https://walletsandbelts.com/'))->settingsChannel());
        $this->assertSame('wholesale', StorefrontContext::fromRequest(Request::create('https://igicanada.ca/'))->settingsChannel());
        $this->assertSame('retail', StorefrontContext::fromRequest(Request::create('https://www.leatherwallets.ca/'))->settingsChannel());
    }

    public function test_retail_sibling_domain_uses_its_own_homepage_settings(): void
    {
        HomepageSetting::query()->where('sales_channel', 'retail')->update([
            'brand_name' => 'Leather Wallets Test',
            'hero_title' => 'Retail-only hero',
            'announcement_text' => 'Leather Wallets announcement',
        ]);

        HomepageSetting::query()->where('sales_channel', 'walletsandbelts')->update([
            'brand_name' => 'Wallets and Belts Test',
            'hero_title' => 'Wallets and belts hero',
            'announcement_text' => 'Wallets and belts announcement',
        ]);

        $this->get('https://leatherwallets.ca/')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('salesChannel', 'retail')
                ->where('homepage.heroTitle', 'Retail-only hero')
                ->where('retailStorefront.brandName', 'Leather Wallets Test')
                ->where('retailStorefront.announcement', 'Leather Wallets announcement'));

        $this->get('https://walletsandbelts.com/')
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page
                ->where('salesChannel', 'retail')
                ->where('homepage.heroTitle', 'Wallets and belts hero')
                ->where('retailStorefront.brandName', 'Wallets and Belts Test')
                ->where('retailStorefront.announcement', 'Wallets and belts announcement'));
    }

    public function test_admin_can_manage_walletsandbelts_homepage_settings(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $wholesale = HomepageSetting::query()->where('sales_channel', 'wholesale')->firstOrFail();
        $retail = HomepageSetting::query()->where('sales_channel', 'retail')->firstOrFail();
        $wallet = HomepageSetting::query()->where('sales_channel', 'walletsandbelts')->firstOrFail();

        AdminStorefront::select('walletsandbelts');

        Livewire::test(ListHomepageSettings::class)
            ->assertCanSeeTableRecords([$wallet])
            ->assertCanNotSeeTableRecords([$retail, $wholesale]);

        Livewire::test(EditHomepageSetting::class, ['record' => $wallet->getKey()])
            ->assertSuccessful()
            ->assertSee('Wallets and Belts homepage');
    }
}