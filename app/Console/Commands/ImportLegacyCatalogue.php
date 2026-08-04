<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('legacy:import-catalogue {--dry-run : Validate the source and show counts without writing} {--include-inactive : Import inactive catalogue records}')]
#[Description('Import the authoritative Yii catalogue into the normalized commerce schema')]
class ImportLegacyCatalogue extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $legacy = DB::connection('legacy');
            $tables = ['igi_category', 'igi_posts', 'igi_post_variation', 'igi_post_attachments'];

            foreach ($tables as $table) {
                if (! $legacy->getSchemaBuilder()->hasTable($table)) {
                    $this->error("Missing legacy table: {$table}");

                    return self::FAILURE;
                }
            }

            $counts = collect($tables)->mapWithKeys(fn (string $table) => [$table => $legacy->table($table)->count()]);
            $this->table(['Legacy table', 'Rows'], $counts->map(fn ($count, $table) => [$table, $count])->values());

            if ($this->option('dry-run')) {
                return self::SUCCESS;
            }

            DB::transaction(function () use ($legacy): void {
                $includeInactive = (bool) $this->option('include-inactive');
                $categories = $legacy->table('igi_category')->orderBy('id')->get();

                foreach ($categories as $source) {
                    Category::updateOrCreate(['legacy_id' => $source->id], [
                        'name' => $source->name,
                        'slug' => $this->uniqueSlug(Category::class, $source->slug, (int) $source->id),
                        'description' => $source->content ?: null,
                        'image_path' => $source->attachment ?: null,
                        'position' => 0,
                        'is_active' => (bool) $source->status,
                    ]);
                }

                foreach ($categories as $source) {
                    if ((int) $source->parent_id > 0) {
                        Category::where('legacy_id', $source->id)->update([
                            'parent_id' => Category::where('legacy_id', $source->parent_id)->value('id'),
                        ]);
                    }
                }

                $query = $legacy->table('igi_posts')->orderBy('id');
                $query->whereIn('type', ['wholesale', 'both']);
                if (! $includeInactive) {
                    $query->where('status', 1);
                }

                $query->chunkById(100, function ($rows) use ($legacy): void {
                    foreach ($rows as $source) {
                        $product = Product::updateOrCreate(['legacy_id' => $source->id], [
                            'sku' => $source->product_code ?: null,
                            'name' => $source->title,
                            'slug' => $this->uniqueSlug(Product::class, $source->slug, (int) $source->id),
                            'description' => $source->content ?: null,
                            'primary_image_path' => $source->main_image ?: null,
                            'visibility' => 'wholesale',
                            'weight_kg' => $source->weight ?: null,
                            'is_active' => (bool) $source->status,
                            'published_at' => $source->timestamp ?: null,
                        ]);

                        if ($product->primary_image_path) {
                            $primaryAsset = $this->legacyMediaAsset($product->primary_image_path, $product->name);
                            $product->update(['primary_media_asset_id' => $primaryAsset->id]);
                        }

                        $categoryIds = collect(explode(',', (string) $source->category))
                            ->map(fn ($id) => (int) trim($id))->filter()->unique();
                        $product->categories()->sync(Category::whereIn('legacy_id', $categoryIds)->pluck('id'));

                        foreach ($legacy->table('igi_post_variation')->where('post_id', $source->id)->orderBy('sort_order')->get() as $variant) {
                            ProductVariant::updateOrCreate(['legacy_id' => $variant->id], [
                                'product_id' => $product->id,
                                'sku' => null,
                                'color' => $variant->color ?: null,
                                'size' => $variant->size ?: null,
                                'sizes' => $variant->size ? [$variant->size] : null,
                                'wholesale_price' => $variant->wholesale_price,
                                'wholesale_minimum_quantity' => max(1, (int) $variant->wholesale_min_qty),
                                'stock_quantity' => (int) $variant->qty,
                                'is_active' => (bool) $variant->show_hide,
                            ]);
                        }

                        foreach ($legacy->table('igi_post_attachments')->where('post_id', $source->id)->orderBy('sort_order')->get() as $image) {
                            $path = $image->attachment_big ?: $image->attachment;
                            $path = str_starts_with($path, '/') ? $path : '/upload/post/'.$path;
                            $mediaAsset = $this->legacyMediaAsset($path, $product->name);
                            ProductImage::updateOrCreate(['legacy_id' => $image->id], [
                                'product_id' => $product->id,
                                'media_asset_id' => $mediaAsset->id,
                                'path' => $path,
                                'alt_text' => $product->name,
                                'position' => max(0, (int) $image->sort_order),
                                'is_primary' => $source->main_image && str_ends_with($source->main_image, $path),
                            ]);
                        }
                    }
                }, 'id');
            });

            $this->info(sprintf('Imported %d categories, %d products, %d variants, and %d images.', Category::count(), Product::count(), ProductVariant::count(), ProductImage::count()));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Catalogue import failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function legacyMediaAsset(string $path, string $title): MediaAsset
    {
        return MediaAsset::firstOrCreate(
            ['path' => $path],
            [
                'disk' => 'legacy',
                'filename' => basename($path),
                'title' => $title,
                'alt_text' => $title,
            ],
        );
    }

    /**
     * Keep the first legacy URL unchanged and suffix later collisions predictably.
     *
     * @param  class-string<Category|Product>  $model
     */
    private function uniqueSlug(string $model, ?string $slug, int $legacyId): string
    {
        $base = trim((string) $slug) ?: 'legacy-'.$legacyId;
        $owner = $model::query()->where('slug', $base)->first();

        if (! $owner || (int) $owner->legacy_id === $legacyId) {
            return $base;
        }

        return $base.'-legacy-'.$legacyId;
    }
}
