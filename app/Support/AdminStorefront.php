<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class AdminStorefront
{
    public const SESSION_KEY = 'admin_sales_channel';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'all' => 'All Stores',
            'wholesale' => 'IGI Canada · Wholesale',
            'retail' => 'Leather Wallets · Retail',
        ];
    }

    public static function current(): string
    {
        $channel = session()->get(self::SESSION_KEY, 'all');

        return array_key_exists($channel, self::options()) ? $channel : 'all';
    }

    public static function label(): string
    {
        return self::options()[self::current()];
    }

    public static function showsRetailFields(): bool
    {
        return in_array(self::current(), ['all', 'retail'], true);
    }

    public static function showsWholesaleFields(): bool
    {
        return in_array(self::current(), ['all', 'wholesale'], true);
    }

    public static function select(string $channel): void
    {
        session()->put(self::SESSION_KEY, $channel);
    }

    public static function apply(Builder $query, string $column = 'sales_channel'): Builder
    {
        $channel = self::current();

        return $channel === 'all' ? $query : $query->where($column, $channel);
    }

    public static function applyProductVisibility(Builder $query): Builder
    {
        return match (self::current()) {
            'wholesale' => $query->whereIn('visibility', ['wholesale', 'both']),
            'retail' => $query->whereIn('visibility', ['retail', 'both']),
            default => $query,
        };
    }

    public static function applyVisibility(Builder $query, string $column = 'visibility'): Builder
    {
        return match (self::current()) {
            'wholesale' => $query->whereIn($column, ['wholesale', 'both']),
            'retail' => $query->whereIn($column, ['retail', 'both']),
            default => $query,
        };
    }
}
