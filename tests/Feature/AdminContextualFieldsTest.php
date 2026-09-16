<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\HomepageSettings\Pages\EditHomepageSetting;
use App\Filament\Resources\NavigationItems\NavigationItemResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\StandardShippingRates\StandardShippingRateResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Category;
use App\Models\HomepageSetting;
use App\Models\NavigationItem;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminStorefront;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContextualFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_fields_follow_the_selected_website_and_preserve_hidden_channel_data(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $product = Product::create([
            'name' => 'Shared wallet',
            'retail_name' => 'Retail wallet',
            'slug' => 'shared-wallet',
            'description' => '<p>Wholesale copy</p>',
            'retail_description' => '<p>Retail copy</p>',
            'visibility' => 'both',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'sku' => 'WALLET-01',
            'wholesale_price' => 20,
            'wholesale_minimum_quantity' => 6,
            'retail_price' => 49,
            'retail_compare_at_price' => 59,
            'stock_quantity' => 10,
            'is_available_wholesale' => true,
            'is_available_retail' => true,
            'is_active' => true,
        ]);

        AdminStorefront::select('retail');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('description')
            ->assertFormFieldDoesNotExist('retail_name')
            ->assertFormFieldDoesNotExist('retail_description')
            ->assertFormFieldDoesNotExist('visibility')
            ->fillForm(['name' => 'Updated shared wallet'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated shared wallet', $product->fresh()->name);
        $this->assertSame('Retail wallet', $product->fresh()->retail_name);
        $this->assertSame('<p>Wholesale copy</p>', $product->fresh()->description);
        $this->assertSame('20.00', $variant->fresh()->wholesale_price);
        $this->assertTrue($variant->fresh()->is_available_wholesale);

        AdminStorefront::select('wholesale');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('description')
            ->assertFormFieldDoesNotExist('retail_name')
            ->assertFormFieldDoesNotExist('retail_description')
            ->assertFormFieldDoesNotExist('visibility');

        AdminStorefront::select('all');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('description')
            ->assertFormFieldDoesNotExist('retail_name')
            ->assertFormFieldDoesNotExist('retail_description')
            ->assertFormFieldExists('visibility');

        AdminStorefront::select('retail');

        Livewire::test(CreateProduct::class)
            ->assertFormFieldExists('name')
            ->assertFormFieldDoesNotExist('retail_name')
            ->fillForm([
                'name' => 'Retail-only card holder',
                'slug' => 'retail-only-card-holder',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Retail-only card holder',
            'slug' => 'retail-only-card-holder',
            'visibility' => 'both',
        ]);
    }

    public function test_homepage_fields_follow_the_homepage_website(): void
    {
        $this->actingAs($this->admin(), 'admin');
        AdminStorefront::select('all');

        $retail = HomepageSetting::query()->where('sales_channel', 'retail')->firstOrFail();
        $wholesale = HomepageSetting::query()->where('sales_channel', 'wholesale')->firstOrFail();

        Livewire::test(EditHomepageSetting::class, ['record' => $retail->getRouteKey()])
            ->assertFormFieldExists('sales_channel')
            ->assertFormFieldDoesNotExist('show_category_menu')
            ->assertFormFieldDoesNotExist('category_menu_label');

        Livewire::test(EditHomepageSetting::class, ['record' => $wholesale->getRouteKey()])
            ->assertFormFieldExists('sales_channel')
            ->assertFormFieldExists('show_category_menu')
            ->assertFormFieldExists('category_menu_label');

        AdminStorefront::select('retail');

        Livewire::test(EditHomepageSetting::class, ['record' => $retail->getRouteKey()])
            ->assertFormFieldDoesNotExist('sales_channel');
    }

    public function test_product_listing_columns_and_records_follow_the_selected_website(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $wholesale = Product::create([
            'name' => 'Wholesale belt',
            'slug' => 'wholesale-belt',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $retail = Product::create([
            'name' => 'Internal card holder',
            'retail_name' => 'Retail card holder',
            'slug' => 'retail-card-holder',
            'primary_image_path' => '/storage/https%3A//igicanada.ca/upload/post/Retail Wallet.jpg',
            'visibility' => 'retail',
            'is_active' => true,
        ]);

        AdminStorefront::select('retail');

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$retail])
            ->assertCanNotSeeTableRecords([$wholesale])
            ->assertTableColumnVisible('name')
            ->assertTableColumnHidden('minimum_wholesale_price')
            ->assertTableColumnVisible('minimum_retail_price')
            ->assertTableColumnHidden('visibility');

        AdminStorefront::select('wholesale');

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$wholesale])
            ->assertCanNotSeeTableRecords([$retail])
            ->assertTableColumnVisible('name')
            ->assertTableColumnVisible('minimum_wholesale_price')
            ->assertTableColumnHidden('minimum_retail_price')
            ->assertTableColumnHidden('visibility');

        AdminStorefront::select('all');

        $listing = Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$wholesale, $retail])
            ->assertTableColumnVisible('name')
            ->assertTableColumnVisible('variants_count');

        $defaultColumns = collect($listing->instance()->tableColumns)->keyBy('name');
        $this->assertSame('14rem', $listing->instance()->getTable()->getColumn('name')->getWidth());
        foreach (['visibility', 'retail_ready_variants_count', 'minimum_retail_price', 'minimum_wholesale_price', 'is_active', 'legacy_id', 'slug', 'created_at', 'updated_at', 'published_at'] as $column) {
            $this->assertFalse($defaultColumns[$column]['isToggled'], "Expected {$column} to be hidden by default.");
        }

        foreach (['sku', 'primary_image_url', 'name', 'categories.name', 'variants_count', 'available_stock_quantity', 'weight_kg'] as $column) {
            $this->assertTrue($defaultColumns[$column]['isToggled'], "Expected {$column} to be visible by default.");
        }

        $this->get('/admin/products')
            ->assertSuccessful()
            ->assertSee('https://igicanada.ca/upload/post/Retail%20Wallet.jpg', false)
            ->assertDontSee('https%3A//igicanada.ca', false);
    }

    public function test_product_category_show_more_keeps_extra_categories_in_the_page(): void
    {
        $this->actingAs($this->admin(), 'admin');
        AdminStorefront::select('all');

        $product = Product::create([
            'name' => 'Multi-category wallet',
            'slug' => 'multi-category-wallet',
            'visibility' => 'both',
            'is_active' => true,
        ]);

        $categories = collect(range(1, 4))->map(fn (int $number): Category => Category::create([
            'name' => "Expandable category {$number}",
            'slug' => "expandable-category-{$number}",
            'visibility' => 'both',
            'is_active' => true,
        ]));
        $product->categories()->attach($categories->pluck('id'));

        Livewire::test(ListProducts::class)
            ->assertSee('Expandable category 1')
            ->assertSee('Expandable category 2')
            ->assertSee('Expandable category 3')
            ->assertSee('Expandable category 4')
            ->assertSee('Show 1 more');
    }

    public function test_customer_fields_follow_the_customer_and_selected_website(): void
    {
        $this->actingAs($this->admin(), 'admin');
        AdminStorefront::select('all');

        $retail = User::factory()->create([
            'account_type' => 'retail',
            'approval_status' => 'approved',
        ]);
        $wholesale = User::factory()->create([
            'account_type' => 'wholesale',
            'approval_status' => 'approved',
        ]);

        Livewire::test(EditUser::class, ['record' => $retail->getRouteKey()])
            ->assertFormFieldExists('account_type')
            ->assertFormFieldDoesNotExist('resellerProfile.company')
            ->assertFormFieldDoesNotExist('approval_status')
            ->assertFormFieldDoesNotExist('price_tier_id');

        Livewire::test(EditUser::class, ['record' => $wholesale->getRouteKey()])
            ->assertFormFieldExists('account_type')
            ->assertFormFieldExists('resellerProfile.company')
            ->assertFormFieldExists('approval_status')
            ->assertFormFieldExists('price_tier_id');

        AdminStorefront::select('retail');

        Livewire::test(EditUser::class, ['record' => $retail->getRouteKey()])
            ->assertFormFieldDoesNotExist('account_type');
    }

    public function test_categories_menus_and_shipping_queries_follow_the_selected_website(): void
    {
        $wholesaleCategory = Category::create([
            'name' => 'Wholesale category',
            'slug' => 'wholesale-category',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $retailCategory = Category::create([
            'name' => 'Retail category',
            'slug' => 'retail-category',
            'visibility' => 'retail',
            'is_active' => true,
        ]);
        $sharedCategory = Category::create([
            'name' => 'Shared category',
            'slug' => 'shared-category',
            'visibility' => 'both',
            'is_active' => true,
        ]);
        $wholesaleMenu = NavigationItem::create([
            'sales_channel' => 'wholesale',
            'location' => 'header',
            'label' => 'Wholesale menu',
            'url' => '/catalogue',
            'is_active' => true,
        ]);
        $retailMenu = NavigationItem::create([
            'sales_channel' => 'retail',
            'location' => 'header',
            'label' => 'Retail menu',
            'url' => '/shop',
            'is_active' => true,
        ]);

        AdminStorefront::select('retail');
        $this->assertEqualsCanonicalizing(
            [$retailCategory->id, $sharedCategory->id],
            CategoryResource::getEloquentQuery()->whereKey([$wholesaleCategory->id, $retailCategory->id, $sharedCategory->id])->pluck('id')->all(),
        );
        $this->assertSame([$retailMenu->id], NavigationItemResource::getEloquentQuery()->whereKey([$wholesaleMenu->id, $retailMenu->id])->pluck('id')->all());
        $this->assertTrue(StandardShippingRateResource::getEloquentQuery()->get()->every(fn ($rate): bool => $rate->sales_channel === 'retail'));

        AdminStorefront::select('wholesale');
        $this->assertEqualsCanonicalizing(
            [$wholesaleCategory->id, $sharedCategory->id],
            CategoryResource::getEloquentQuery()->whereKey([$wholesaleCategory->id, $retailCategory->id, $sharedCategory->id])->pluck('id')->all(),
        );
        $this->assertSame([$wholesaleMenu->id], NavigationItemResource::getEloquentQuery()->whereKey([$wholesaleMenu->id, $retailMenu->id])->pluck('id')->all());
        $this->assertTrue(StandardShippingRateResource::getEloquentQuery()->get()->every(fn ($rate): bool => $rate->sales_channel === 'wholesale'));
    }

    public function test_product_list_switches_visibility_per_product_row(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $product = Product::create([
            'name' => 'Switchable wallet',
            'slug' => 'switchable-wallet',
            'visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::test(ListProducts::class)
            ->callTableAction('changeVisibility', $product, ['visibility' => 'retail'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('retail', $product->fresh()->visibility);

        Livewire::test(ListProducts::class)
            ->callTableAction('changeVisibility', $product, ['visibility' => 'wholesale'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('wholesale', $product->fresh()->visibility);

        Livewire::test(ListProducts::class)
            ->callTableAction('changeVisibility', $product, ['visibility' => 'both'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('both', $product->fresh()->visibility);
    }

    public function test_product_list_bulk_action_sets_visibility_for_selected_products(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $wholesaleOnly = Product::create([
            'name' => 'Wholesale belt',
            'slug' => 'wholesale-belt',
            'visibility' => 'wholesale',
            'is_active' => true,
        ]);
        $retailOnly = Product::create([
            'name' => 'Retail card holder',
            'slug' => 'retail-card-holder',
            'visibility' => 'retail',
            'is_active' => true,
        ]);

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('setVisibility', [$wholesaleOnly, $retailOnly], ['visibility' => 'both'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('both', $wholesaleOnly->fresh()->visibility);
        $this->assertSame('both', $retailOnly->fresh()->visibility);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'account_type' => 'admin',
            'approval_status' => 'approved',
            'admin_sales_channel' => 'all',
            'email_verified_at' => now(),
        ]);
    }
}
