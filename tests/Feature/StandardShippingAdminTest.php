<?php

namespace Tests\Feature;

use App\Filament\Resources\StandardShippingRates\Pages\ListStandardShippingRates;
use App\Models\StandardShippingRate;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StandardShippingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_admin_can_open_shipping_charge_bulk_editor(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/standard-shipping-rates')
            ->assertSuccessful()
            ->assertSee('Bulk edit charges');
    }

    public function test_bulk_editor_contains_only_rates_matching_the_active_country_tab(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin, 'admin');

        Livewire::test(ListStandardShippingRates::class)
            ->set('activeTab', 'CA')
            ->mountAction('bulkEdit')
            ->assertActionDataSet(function (array $data): array {
                $rates = collect($data['rates']);
                $this->assertCount(15, $rates);
                $this->assertTrue($rates->every(fn (array $rate): bool => $rate['country'] === 'CA'));
                $this->assertEqualsCanonicalizing(['retail', 'wholesale', 'walletsandbelts'], $rates->pluck('sales_channel')->unique()->values()->all());

                return [];
            });
    }

    public function test_list_page_separates_rates_into_country_tabs(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin, 'admin');

        Livewire::test(ListStandardShippingRates::class)
            ->set('tableRecordsPerPage', 50)
            ->assertSet('activeTab', 'CA')
            ->assertSee('Canada')
            ->assertSee('USA')
            ->assertCanSeeTableRecords(
                StandardShippingRate::query()->where('country', 'CA')->get(),
            )
            ->assertCanNotSeeTableRecords(
                StandardShippingRate::query()->where('country', 'US')->get(),
            )
            ->set('activeTab', 'US')
            ->assertCanSeeTableRecords(
                StandardShippingRate::query()->where('country', 'US')->get(),
            )
            ->assertCanNotSeeTableRecords(
                StandardShippingRate::query()->where('country', 'CA')->get(),
            );
    }
}
