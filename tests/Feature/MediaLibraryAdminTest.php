<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Livewire\MediaPickerUploader;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaLibraryAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_media_library_folders_and_product_media_form(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
            'email_verified_at' => now(),
        ]);
        $folder = MediaFolder::create(['name' => 'Products']);
        $asset = MediaAsset::create([
            'media_folder_id' => $folder->id,
            'disk' => 'public',
            'path' => 'media-library/product.jpg',
            'filename' => 'product.jpg',
            'title' => 'Product image',
        ]);
        $secondAsset = MediaAsset::create([
            'media_folder_id' => $folder->id,
            'disk' => 'public',
            'path' => 'media-library/product-detail.jpg',
            'filename' => 'product-detail.jpg',
            'title' => 'Product detail image',
        ]);
        $product = Product::create([
            'name' => 'Media Product',
            'slug' => 'media-product',
            'visibility' => 'wholesale',
            'is_active' => true,
            'primary_media_asset_id' => $asset->id,
        ]);
        $product->mediaAssets()->attach($asset);

        $this->actingAs($admin)->get('/admin/media-assets')->assertSuccessful()->assertSee('Media library');
        $this->actingAs($admin)->get('/admin/media-assets/create')->assertSuccessful()->assertSee('Image');
        $this->actingAs($admin)->get("/admin/media-assets/{$asset->id}/edit")->assertSuccessful()->assertSee('Product image');
        $this->actingAs($admin)->get('/admin/media-folders')->assertSuccessful()->assertSee('Products');
        $this->actingAs($admin)->get("/admin/products/{$product->id}/edit")
            ->assertSuccessful()
            ->assertSee('Primary image')
            ->assertSee('primary-image-thumbnail-select', false)
            ->assertSee('/storage/media-library/product.jpg', false)
            ->assertSee('Product images (select multiple)')
            ->assertSee('product-images-thumbnail-select', false)
            ->assertSee('width:3.5rem', false)
            ->assertSee('grid-template-columns: 2rem minmax(0, 1fr)', false)
            ->assertSee('grid-template-columns: repeat(6, minmax(0, 11rem))', false)
            ->assertSee('justify-content: center !important', false)
            ->assertSee('align-self: start', false)
            ->assertDontSee('Legacy ID')
            ->assertSee('Visibility');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormComponentActionExists('primary_media_asset_id', 'select')
            ->assertFormComponentActionHasLabel('primary_media_asset_id', 'select', 'Open media library and select primary image')
            ->mountFormComponentAction('primary_media_asset_id', 'select')
            ->unmountFormComponentAction()
            ->assertFormComponentActionExists('mediaAssets', 'select')
            ->assertFormComponentActionHasLabel('mediaAssets', 'select', 'Open media library and select images')
            ->mountFormComponentAction('mediaAssets', 'select')
            ->unmountFormComponentAction()
            ->fillForm([
                'primary_media_asset_id' => $secondAsset->id,
                'mediaAssets' => [$asset->id, $secondAsset->id],
            ])
            ->assertSee('/storage/media-library/product-detail.jpg', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($secondAsset->id, $product->fresh()->primary_media_asset_id);
        $this->assertEqualsCanonicalizing(
            [$asset->id, $secondAsset->id],
            $product->fresh()->mediaAssets()->pluck('media_assets.id')->all(),
        );
    }

    public function test_new_admin_products_default_to_wholesale_with_a_channel_visibility_field(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateProduct::class)
            ->assertFormFieldDoesNotExist('legacy_id')
            ->assertFormFieldExists('visibility')
            ->fillForm([
                'name' => 'Wholesale Only Product',
                'slug' => 'wholesale-only-product',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'slug' => 'wholesale-only-product',
            'visibility' => 'wholesale',
        ]);
    }

    public function test_admin_can_auto_upload_unfiled_images_inside_the_media_picker(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'email_verified_at' => now(),
        ]);
        Livewire::actingAs($admin)
            ->test(MediaPickerUploader::class)
            ->set('uploads', [UploadedFile::fake()->image('brown-belt.jpg')])
            ->assertHasNoErrors()
            ->assertDispatched('media-picker-uploaded');

        $asset = MediaAsset::query()->where('filename', 'brown-belt.jpg')->firstOrFail();

        $this->assertNull($asset->media_folder_id);
        $this->assertSame('Brown Belt', $asset->title);
        $this->assertSame('/storage/'.$asset->path, $asset->preview_url);
        $this->assertSame('/storage/'.$asset->path, Storage::disk('public')->url($asset->path));
        Storage::disk('public')->assertExists($asset->path);
    }
}
