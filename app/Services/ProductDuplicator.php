<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copies a product with its variants, images and categories.
 *
 * The copy always starts unpublished and with 0 stock on every variant, so it
 * never appears on a storefront or gets sold until an admin reviews it.
 */
class ProductDuplicator
{
    public function duplicate(Product $source, ?string $name = null): Product
    {
        return DB::transaction(function () use ($source, $name): Product {
            $source->loadMissing(['variants', 'images', 'categories']);

            $name = filled($name) ? trim($name) : $source->name.' (Copy)';

            $copy = $source->replicate(['legacy_id']);
            $copy->name = $name;
            $copy->slug = $this->uniqueValue(Str::slug($name) ?: 'product-copy', fn (string $v): bool => Product::where('slug', $v)->exists());
            $copy->sku = filled($source->sku)
                ? $this->uniqueValue($source->sku.'-COPY', fn (string $v): bool => Product::where('sku', $v)->exists())
                : null;
            $copy->is_active = false;
            $copy->published_at = null;
            $copy->save();

            $variantMap = [];
            foreach ($source->variants as $variant) {
                $newVariant = $variant->replicate(['legacy_id']);
                $newVariant->product_id = $copy->id;
                $newVariant->stock_quantity = 0;
                $newVariant->sku = filled($variant->sku)
                    ? $this->uniqueValue($variant->sku.'-COPY', fn (string $v): bool => ProductVariant::where('sku', $v)->exists())
                    : null;
                $newVariant->save();

                $variantMap[$variant->id] = $newVariant->id;
            }

            foreach ($source->images as $image) {
                /** @var ProductImage $newImage */
                $newImage = $image->replicate(['legacy_id']);
                $newImage->product_id = $copy->id;
                $newImage->product_variant_id = $image->product_variant_id
                    ? ($variantMap[$image->product_variant_id] ?? null)
                    : null;
                $newImage->save();
            }

            $copy->categories()->sync(
                $source->categories->mapWithKeys(fn ($category): array => [
                    $category->id => ['position' => $category->pivot->position],
                ])->all(),
            );

            return $copy;
        });
    }

    /** Appends -2, -3, … until the value is free. */
    private function uniqueValue(string $base, callable $taken): string
    {
        $value = $base;
        $i = 2;
        while ($taken($value)) {
            $value = $base.'-'.$i++;
        }

        return $value;
    }
}
