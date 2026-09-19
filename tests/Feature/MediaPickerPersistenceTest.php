<?php

namespace Tests\Feature;

use App\Filament\Livewire\PersistentTableSelectLivewireComponent;
use App\Filament\Resources\MediaAssets\Tables\MediaAssetsPickerTable;
use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MediaPickerPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    /** @return array<string, mixed> */
    private function pickerProperties(): array
    {
        return [
            'isDisabled' => false,
            'maxSelectableRecords' => 1,
            'model' => Product::class,
            'record' => $this->product,
            'relationshipName' => 'primaryMedia',
            'shouldIgnoreRelatedRecords' => false,
            'tableConfiguration' => base64_encode(MediaAssetsPickerTable::class),
            'tableArguments' => [],
            'wire:model' => 'data.primary_media_asset_id',
        ];
    }

    public function test_media_picker_restores_search_and_page_between_opens(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        for ($i = 0; $i < 30; $i++) {
            MediaAsset::create([
                'disk' => 'public',
                'path' => "media-library/wallet-{$i}.jpg",
                'filename' => "wallet-{$i}.jpg",
                'title' => "Wallet image {$i}",
            ]);
        }

        $this->product = Product::create([
            'name' => 'Persistent Picker Product',
            'slug' => 'persistent-picker-product',
            'visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin, 'admin');

        Livewire::test(PersistentTableSelectLivewireComponent::class, $this->pickerProperties())
            ->assertSet('tableSearch', '')
            ->set('tableSearch', 'Wallet image 2')
            ->call('setPage', 2, 'page')
            ->assertSet('tableSearch', 'Wallet image 2')
            ->assertSet('paginators.page', 2);

        Livewire::test(PersistentTableSelectLivewireComponent::class, $this->pickerProperties())
            ->assertSet('tableSearch', 'Wallet image 2')
            ->assertSet('paginators.page', 2)
            ->call('setPage', 1, 'page')
            ->assertSet('paginators.page', 1);
    }

    public function test_media_picker_search_is_shared_across_picker_instances(): void
    {
        $admin = User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'email_verified_at' => now(),
        ]);

        MediaAsset::create([
            'disk' => 'public',
            'path' => 'media-library/vintage-brown.jpg',
            'filename' => 'vintage-brown.jpg',
            'title' => 'Vintage brown wallet',
        ]);

        $this->product = Product::create([
            'name' => 'Shared Picker Product',
            'slug' => 'shared-picker-product',
            'visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin, 'admin');

        Livewire::test(PersistentTableSelectLivewireComponent::class, $this->pickerProperties())
            ->set('tableSearch', 'vintage brown');

        Livewire::test(PersistentTableSelectLivewireComponent::class, $this->pickerProperties())
            ->assertSet('tableSearch', 'vintage brown')
            ->assertSee('Vintage brown wallet');
    }
}