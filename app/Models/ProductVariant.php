<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
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
