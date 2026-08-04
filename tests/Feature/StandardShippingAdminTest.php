<?php

namespace Tests\Feature;

use App\Filament\Resources\StandardShippingRates\Pages\ListStandardShippingRates;
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
        ]);

        $this->actingAs($admin)
            ->get('/admin/standard-shipping-rates')
            ->assertSuccessful()
            ->assertSee('Bulk edit charges');
    }

    public function test_bulk_editor_contains_only_rates_matching_the_country_filter(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
        ]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(ListStandardShippingRates::class)
            ->filterTable('country', 'CA')
            ->mountAction('bulkEdit')
            ->assertActionDataSet(function (array $data): array {
                $rates = collect($data['rates']);
                $this->assertCount(5, $rates);
                $this->assertTrue($rates->every(fn (array $rate): bool => $rate['country'] === 'CA'));

                return [];
            });
    }
}
