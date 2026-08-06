<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RetailProductActivationService
{
    public function enable(Product $product): int
    {
        if (! $product->is_active) {
            return 0;
        }

        return DB::transaction(function () use ($product): int {
            $enabledVariants = $product->variants()
                ->where('is_active', true)
                ->whereNotNull('retail_price')
                ->where('retail_price', '>', 0)
                ->where('stock_quantity', '>', 0)
                ->update(['is_available_retail' => true]);

            if ($enabledVariants > 0 && $product->visibility === 'wholesale') {
                $product->update(['visibility' => 'both']);
            }

            return $enabledVariants;
        });
    }

    public function disable(Product $product): int
    {
        return $product->variants()->update(['is_available_retail' => false]);
    }
}
