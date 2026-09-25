<?php

namespace Tests\Feature;

use App\Filament\Resources\StandardShippingRates\Pages\CreateStandardShippingRate;
use App\Models\StandardShippingRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StandardShippingCreatePageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_create_page_rejects_an_overlapping_slab_with_an_inline_error(): void
    {
        $this->admin();

        Livewire::test(CreateStandardShippingRate::class)
            ->fillForm([
                'sales_channel' => 'wholesale',
                'country' => 'CA',
                'name' => 'Test2',
                'min_order_amount' => 4,
                'max_order_amount' => 55,
                'charge' => 6,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['min_order_amount'])
            ->assertSee('This amount range overlaps another active rate');

        $this->assertDatabaseMissing('standard_shipping_rates', [
            'country' => 'CA',
            'name' => 'Test2',
            'min_order_amount' => 4,
        ]);
    }

    public function test_create_page_saves_a_valid_slab_when_the_range_is_free(): void
    {
        $this->admin();

        StandardShippingRate::query()
            ->where('sales_channel', 'wholesale')
            ->where('country', 'CA')
            ->where('min_order_amount', 0)
            ->update(['is_active' => false]);

        Livewire::test(CreateStandardShippingRate::class)
            ->fillForm([
                'sales_channel' => 'wholesale',
                'country' => 'CA',
                'name' => 'Test2',
                'min_order_amount' => 4,
                'max_order_amount' => 55,
                'charge' => 6,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('standard_shipping_rates', [
            'sales_channel' => 'wholesale',
            'country' => 'CA',
            'name' => 'Test2',
            'min_order_amount' => 4,
            'max_order_amount' => 55,
            'charge' => 6,
        ]);
    }

    public function test_create_page_rejects_max_being_below_min_with_an_inline_error(): void
    {
        $this->admin();

        Livewire::test(CreateStandardShippingRate::class)
            ->fillForm([
                'sales_channel' => 'wholesale',
                'country' => 'US',
                'name' => 'Broken range',
                'min_order_amount' => 500,
                'max_order_amount' => 100,
                'charge' => 6,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['min_order_amount'])
            ->assertSee('Maximum order amount must be greater than or equal to the minimum amount.');
    }
}