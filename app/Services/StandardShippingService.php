<?php

namespace App\Services;

use App\Models\StandardShippingRate;
use InvalidArgumentException;

class StandardShippingService
{
    /**
     * @return array{code:string, name:string, price:string}
     */
    public function quote(float $subtotal, string $country, ?string $countryCode = null, string $salesChannel = 'wholesale'): array
    {
        $destination = $this->destination($country, $countryCode);
        $rate = StandardShippingRate::query()
            ->where('sales_channel', $salesChannel)
            ->where('country', $destination)
            ->where('is_active', true)
            ->where('min_order_amount', '<=', $subtotal)
            ->where('max_order_amount', '>=', $subtotal)
            ->orderBy('min_order_amount')
            ->first();

        if ($rate) {
            return [
                'code' => 'STANDARD',
                'name' => $rate->name,
                'price' => number_format((float) $rate->charge, 2, '.', ''),
            ];
        }

        throw new InvalidArgumentException('Standard Shipping is unavailable for this order total. Please submit an enquiry for assistance.');
    }

    private function destination(string $country, ?string $countryCode): string
    {
        $code = strtoupper(trim((string) $countryCode));
        $name = strtolower(trim($country));

        return match (true) {
            $code === 'CA' || $name === 'canada' => 'CA',
            $code === 'US' || in_array($name, ['usa', 'us', 'united states', 'united states of america'], true) => 'US',
            default => throw new InvalidArgumentException('Standard Shipping is available only within Canada and the United States.'),
        };
    }
}
