<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class StandardShippingRate extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (StandardShippingRate $rate): void {
            if (! $rate->is_active) {
                return;
            }

            if ((float) $rate->max_order_amount < (float) $rate->min_order_amount) {
                throw ValidationException::withMessages([
                    'max_order_amount' => 'Maximum order amount must be greater than or equal to the minimum amount.',
                ]);
            }

            $overlapExists = static::query()
                ->where('country', $rate->country)
                ->where('is_active', true)
                ->when($rate->exists, fn ($query) => $query->whereKeyNot($rate->getKey()))
                ->where('min_order_amount', '<=', $rate->max_order_amount)
                ->where('max_order_amount', '>=', $rate->min_order_amount)
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'min_order_amount' => 'This amount range overlaps another active rate for the selected country.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'min_order_amount' => 'decimal:2',
            'max_order_amount' => 'decimal:2',
            'charge' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
