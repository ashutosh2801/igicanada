<?php

namespace App\Models;

use App\Support\StorefrontAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('position');
    }

    public function imageMediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_media_asset_id');
    }

    public function imageUrl(): ?string
    {
        if ($this->imageMediaAsset) {
            return $this->imageMediaAsset->url();
        }

        if (blank($this->image_path)) {
            return null;
        }

        if (str_starts_with($this->image_path, '/storage/')) {
            return $this->image_path;
        }

        if (! str_starts_with($this->image_path, '/') && Storage::disk('public')->exists($this->image_path)) {
            return '/storage/'.ltrim($this->image_path, '/');
        }

        return StorefrontAsset::legacy($this->image_path, 'category');
    }

    public function scopeVisibleForChannel(Builder $query, string $channel): Builder
    {
        return $query->whereIn('visibility', [$channel, 'both']);
    }
}
