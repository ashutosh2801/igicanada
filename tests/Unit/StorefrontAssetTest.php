<?php

namespace Tests\Unit;

use App\Filament\Tables\Columns\DirectImageColumn;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\StorefrontAsset;
use Tests\TestCase;

class StorefrontAssetTest extends TestCase
{
    public function test_igi_canada_urls_are_always_returned_directly(): void
    {
        $direct = 'https://igicanada.ca/upload/post/wallet.jpg';
        $incorrectlyPrefixed = '/storage/'.$direct;

        $this->assertSame($direct, StorefrontAsset::directUrl($direct));
        $this->assertSame($direct, StorefrontAsset::directUrl($incorrectlyPrefixed));
        $this->assertSame($direct, StorefrontAsset::directUrl('/storage/https%3A//igicanada.ca/upload/post/wallet.jpg'));
        $this->assertSame($direct, StorefrontAsset::directUrl('https%3A%2F%2Figicanada.ca%2Fupload%2Fpost%2Fwallet.jpg'));
        $this->assertSame($direct, StorefrontAsset::directUrl('https%253A%252F%252Figicanada.ca%252Fupload%252Fpost%252Fwallet.jpg'));
        $this->assertSame($direct, StorefrontAsset::uploaded($incorrectlyPrefixed));
        $this->assertSame($direct, StorefrontAsset::legacy($incorrectlyPrefixed));

        $media = new MediaAsset(['disk' => 'public', 'path' => $incorrectlyPrefixed]);
        $this->assertSame($direct, $media->url());

        $product = new Product(['primary_image_path' => $incorrectlyPrefixed]);
        $this->assertSame($direct, $product->primaryImageUrl());

        $productImage = new ProductImage(['path' => $incorrectlyPrefixed]);
        $this->assertSame($direct, $productImage->url());
    }

    public function test_local_uploads_still_use_laravel_storage_urls(): void
    {
        $this->assertSame('/storage/products/wallet.jpg', StorefrontAsset::uploaded('products/wallet.jpg'));

        $media = new MediaAsset(['disk' => 'public', 'path' => 'products/wallet.jpg']);
        $this->assertSame('/storage/products/wallet.jpg', $media->url());
    }

    public function test_direct_legacy_urls_remain_valid_when_filenames_contain_spaces(): void
    {
        $url = StorefrontAsset::legacy('/upload/post/Brown Leather Wallet.jpg');

        $this->assertSame('https://igicanada.ca/upload/post/Brown%20Leather%20Wallet.jpg', $url);
        $this->assertNotFalse(filter_var($url, FILTER_VALIDATE_URL));
    }

    public function test_filament_image_columns_bypass_laravel_storage_for_igi_urls(): void
    {
        $column = DirectImageColumn::make('image');

        $this->assertSame(
            'https://igicanada.ca/upload/post/Brown%20Wallet.jpg',
            $column->getImageUrl('/storage/https%3A//igicanada.ca/upload/post/Brown Wallet.jpg'),
        );
    }
}
