<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryImageRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_image_urls_resolve_for_legacy_uploads_and_media_assets(): void
    {
        config()->set('storefronts.legacy_asset_url', 'https://igicanada.ca');

        $legacy = Category::create(['name' => 'Old', 'slug' => 'old', 'image_path' => '7745BlkBrnRed-650x650.jpg']);
        $this->assertSame('https://igicanada.ca/upload/category/7745BlkBrnRed-650x650.jpg', $legacy->imageUrl());

        $media = MediaAsset::create(['disk' => 'public', 'path' => 'categories/bag-front.jpg', 'filename' => 'bag-front.jpg']);
        $new = Category::create(['name' => 'New', 'slug' => 'new', 'image_media_asset_id' => $media->id]);
        $this->assertSame('/storage/categories/bag-front.jpg', $new->imageUrl());

        $none = Category::create(['name' => 'Empty', 'slug' => 'empty']);
        $this->assertNull($none->imageUrl());
    }
}