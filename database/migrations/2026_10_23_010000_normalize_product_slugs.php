<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $badSlug = 'Genuine Cowhide Leather Unisex Messenger bag # 7570';
        $product = DB::table('products')->where('slug', $badSlug)->first(['id', 'name']);

        if (! $product) {
            return;
        }

        $slug = Str::slug($badSlug) ?: 'product-'.$product->id;
        $suffix = 2;
        while (DB::table('products')->where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
            $slug = Str::slug($badSlug).'-'.$suffix++;
        }

        DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
    }

    public function down(): void
    {
        // Normalized slugs cannot be safely restored to their previous values.
    }
};
