<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standard_shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->enum('country', ['CA', 'US']);
            $table->string('name', 100);
            $table->decimal('min_order_amount', 12, 2);
            $table->decimal('max_order_amount', 12, 2);
            $table->decimal('charge', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country', 'min_order_amount']);
            $table->index(['country', 'is_active', 'min_order_amount']);
        });

        $now = now();
        DB::table('standard_shipping_rates')->insert([
            ...$this->rates('US', [18.95, 26.95, 44.95, 69.95, 79.95], 100000, $now),
            ...$this->rates('CA', [18.95, 24.95, 34.95, 59.95, 79.95], 10000, $now),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('standard_shipping_rates');
    }

    /** @return array<int, array<string, mixed>> */
    private function rates(string $country, array $charges, int $lastMaximum, mixed $now): array
    {
        $bands = [
            ['Up to $100', 0, 99.99],
            ['$100 to $200', 100, 199.99],
            ['$200 to $500', 200, 499.99],
            ['$500 to $1,000', 500, 999.99],
            ['$1,000 and above', 1000, $lastMaximum],
        ];

        return collect($bands)->map(fn (array $band, int $index): array => [
            'country' => $country,
            'name' => $band[0],
            'min_order_amount' => $band[1],
            'max_order_amount' => $band[2],
            'charge' => $charges[$index],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();
    }
};
