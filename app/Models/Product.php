<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if ($product->isDirty('primary_media_asset_id') && ! $product->primary_media_asset_id) {
                $product->primary_image_path = null;
            }
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'published_at' => 'datetime', 'weight_kg' => 'decimal:3'];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('position');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function primaryMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'primary_media_asset_id');
    }

    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'product_images')
            ->withPivot(['id', 'position', 'alt_text', 'is_primary'])
            ->withTimestamps();
    }

    public function primaryImageUrl(): ?string
    {
        if ($this->primaryMedia) {
            return $this->primaryMedia->url();
        }

        if (! $this->primary_image_path) {
            return null;
        }

        return str_starts_with($this->primary_image_path, 'http')
            ? $this->primary_image_path
            : 'https://igicanada.ca/'.ltrim($this->primary_image_path, '/');
    }
}
