<?php

namespace App\Models;

use App\Support\StorefrontAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function url(): ?string
    {
        if ($this->mediaAsset) {
            return $this->mediaAsset->url();
        }

        if (! $this->path) {
            return null;
        }

        return StorefrontAsset::legacy($this->path);
    }
}
