<?php

namespace Tests\Unit;

use App\Models\StandardShippingRate;
use App\Services\StandardShippingRateManager;
use App\Services\StandardShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class StandardShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_preserves_canadian_legacy_wholesale_slabs(): void
    {
        $shipping = app(StandardShippingService::class);

        $this->assertSame('18.95', $shipping->quote(99.99, 'Canada', 'CA')['price']);
        $this->assertSame('24.95', $shipping->quote(100, 'Canada', 'CA')['price']);
        $this->assertSame('34.95', $shipping->quote(200, 'Canada', 'CA')['price']);
        $this->assertSame('59.95', $shipping->quote(500, 'Canada', 'CA')['price']);
        $this->assertSame('79.95', $shipping->quote(1000, 'Canada', 'CA')['price']);
    }

    public function test_it_preserves_us_legacy_wholesale_slabs(): void
    {
        $shipping = app(StandardShippingService::class);

        $this->assertSame('18.95', $shipping->quote(99.99, 'USA', 'US')['price']);
        $this->assertSame('26.95', $shipping->quote(100, 'United States', 'US')['price']);
        $this->assertSame('44.95', $shipping->quote(200, 'United States', 'US')['price']);
        $this->assertSame('69.95', $shipping->quote(500, 'United States', 'US')['price']);
        $this->assertSame('79.95', $shipping->quote(1000, 'United States', 'US')['price']);
    }

    public function test_it_rejects_destinations_not_supported_by_the_legacy_table(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(StandardShippingService::class)->quote(100, 'India', 'IN');
    }

    public function test_checkout_uses_a_charge_updated_by_an_administrator(): void
    {
        StandardShippingRate::query()
            ->where('country', 'CA')
            ->where('min_order_amount', 100)
            ->firstOrFail()
            ->update(['charge' => 29.95]);

        $this->assertSame('29.95', app(StandardShippingService::class)->quote(150, 'Canada', 'CA')['price']);
    }

    public function test_checkout_uses_slab_label_minimum_and_maximum_updated_by_an_administrator(): void
    {
        StandardShippingRate::query()
            ->where('country', 'CA')
            ->where('min_order_amount', 100)
            ->firstOrFail()
            ->update([
                'name' => '$110 to $180',
                'min_order_amount' => 110,
                'max_order_amount' => 180,
            ]);

        $quote = app(StandardShippingService::class)->quote(150, 'Canada', 'CA');

        $this->assertSame('$110 to $180', $quote['name']);
        $this->assertSame('24.95', $quote['price']);
    }

    public function test_active_ranges_for_the_same_country_cannot_overlap(): void
    {
        $this->expectException(ValidationException::class);

        StandardShippingRate::create([
            'country' => 'CA',
            'name' => 'Overlapping rate',
            'min_order_amount' => 50,
            'max_order_amount' => 150,
            'charge' => 20,
            'is_active' => true,
        ]);
    }

    public function test_maximum_amount_cannot_be_lower_than_minimum_amount(): void
    {
        $this->expectException(ValidationException::class);

        StandardShippingRate::query()->where('country', 'US')->firstOrFail()->update([
            'min_order_amount' => 50,
            'max_order_amount' => 25,
        ]);
    }

    public function test_bulk_manager_saves_multiple_boundary_changes_together(): void
    {
        $rates = StandardShippingRate::query()->orderBy('country')->orderBy('min_order_amount')->get()
            ->map->only(['id', 'country', 'name', 'min_order_amount', 'max_order_amount', 'charge', 'is_active'])
            ->all();

        foreach ($rates as &$rate) {
            if ($rate['country'] === 'CA' && (float) $rate['min_order_amount'] === 0.0) {
                $rate['max_order_amount'] = 109.99;
            }
            if ($rate['country'] === 'CA' && (float) $rate['min_order_amount'] === 100.0) {
                $rate['name'] = '$110 to $200';
                $rate['min_order_amount'] = 110;
                $rate['charge'] = 30.95;
            }
        }
        unset($rate);

        app(StandardShippingRateManager::class)->update($rates);

        $quote = app(StandardShippingService::class)->quote(110, 'Canada', 'CA');
        $this->assertSame('$110 to $200', $quote['name']);
        $this->assertSame('30.95', $quote['price']);
    }
}
