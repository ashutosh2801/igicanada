<?php

namespace App\Models;

use App\Models\MediaAsset;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class ProductVariant extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_available_wholesale' => 'boolean',
            'is_available_retail' => 'boolean',
            'retail_price' => 'decimal:2',
            'retail_compare_at_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'sizes' => 'array',
            'image_ids' => 'array',
        ];
    }

    /** @return Collection<int, MediaAsset> */
    public function imageAssets(): Collection
    {
        $ids = collect($this->image_ids ?? [])
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return MediaAsset::query()
            ->whereIn('id', $ids)
            ->orderByRaw("FIELD(id, {$ids->implode(',')})")
            ->get();
    }

    /**
     * Query-only relationship used to power the media library picker in
     * Filament. The selected ids are stored in the image_ids column instead.
     */
    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'product_images', 'product_variant_id');
    }

    /** @return array<int, string> */
    public function sizeLabels(): array
    {
        $sizes = collect($this->sizes)
            ->map(fn ($size) => trim((string) $size))
            ->filter()
            ->unique()
            ->values();

        if ($sizes->isEmpty() && filled($this->size)) {
            $sizes->push(trim((string) $this->size));
        }

        return $sizes->all();
    }

    public function optionLabel(): string
    {
        return collect([
            $this->color,
            collect($this->sizeLabels())->join(', '),
        ])->filter()->join(' · ') ?: 'Standard';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
