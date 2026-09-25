<?php

namespace App\Services;

use App\Models\StandardShippingRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StandardShippingRateManager
{
    /** @param array<int, array<string, mixed>> $rates */
    public function update(array $rates): void
    {
        $channels = StandardShippingRate::query()
            ->whereKey(collect($rates)->pluck('id')->filter())
            ->pluck('sales_channel', 'id');
        $rates = collect($rates)
            ->map(function (array $rate) use ($channels): array {
                $rate['sales_channel'] ??= $channels[$rate['id'] ?? null] ?? 'wholesale';

                return $rate;
            })
            ->all();

        $data = Validator::make(['rates' => $rates], [
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.id' => ['required', 'integer', 'distinct', Rule::exists('standard_shipping_rates', 'id')],
            'rates.*.sales_channel' => ['required', Rule::in(['wholesale', 'retail', 'walletsandbelts'])],
            'rates.*.country' => ['required', Rule::in(['CA', 'US'])],
            'rates.*.name' => ['required', 'string', 'max:100'],
            'rates.*.min_order_amount' => ['required', 'numeric', 'min:0'],
            'rates.*.max_order_amount' => ['required', 'numeric', 'min:0'],
            'rates.*.charge' => ['required', 'numeric', 'min:0'],
            'rates.*.is_active' => ['required', 'boolean'],
        ])->validate()['rates'];

        foreach ($data as $index => $rate) {
            if ((float) $rate['max_order_amount'] < (float) $rate['min_order_amount']) {
                throw ValidationException::withMessages([
                    "rates.{$index}.max_order_amount" => 'Maximum order amount must be greater than or equal to the minimum amount.',
                ]);
            }
        }

        $submittedIds = collect($data)->pluck('id');
        $comparisonRates = StandardShippingRate::query()
            ->whereNotIn('id', $submittedIds)
            ->get(['id', 'sales_channel', 'country', 'name', 'min_order_amount', 'max_order_amount', 'charge', 'is_active'])
            ->map(fn (StandardShippingRate $rate): array => [...$rate->toArray(), '_input_index' => null])
            ->concat(collect($data)->map(
                fn (array $rate, int|string $index): array => [...$rate, '_input_index' => $index]
            ));

        foreach ($comparisonRates->where('is_active', true)->groupBy(fn (array $rate): string => $rate['sales_channel'].'|'.$rate['country']) as $countryRates) {
            $previous = null;
            foreach ($countryRates->sortBy('min_order_amount', SORT_NUMERIC) as $rate) {
                if ($previous && (float) $rate['min_order_amount'] <= (float) $previous['max_order_amount']) {
                    $index = $rate['_input_index'] ?? $previous['_input_index'];
                    throw ValidationException::withMessages([
                        "rates.{$index}.min_order_amount" => 'This range overlaps another active rate for the selected country.',
                    ]);
                }
                $previous = $rate;
            }
        }

        DB::transaction(function () use ($data): void {
            foreach ($data as $rate) {
                StandardShippingRate::query()->whereKey($rate['id'])->update([
                    'sales_channel' => $rate['sales_channel'],
                    'country' => $rate['country'],
                    'name' => $rate['name'],
                    'min_order_amount' => $rate['min_order_amount'],
                    'max_order_amount' => $rate['max_order_amount'],
                    'charge' => $rate['charge'],
                    'is_active' => $rate['is_active'],
                ]);
            }
        });
    }
}
