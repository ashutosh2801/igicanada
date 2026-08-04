<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportLegacyCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('legacy');

        Schema::connection('legacy')->create('igi_category', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('parent_id');
            $table->string('name');
            $table->string('slug');
            $table->text('content');
            $table->string('attachment');
            $table->string('position');
            $table->integer('status');
        });
        Schema::connection('legacy')->create('igi_posts', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('category');
            $table->string('title');
            $table->text('content');
            $table->string('product_code')->nullable();
            $table->integer('weight');
            $table->string('slug');
            $table->string('main_image')->nullable();
            $table->integer('status');
            $table->string('type');
            $table->dateTime('timestamp');
        });
        Schema::connection('legacy')->create('igi_post_variation', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('post_id');
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->decimal('price');
            $table->decimal('wholesale_price');
            $table->integer('wholesale_min_qty');
            $table->integer('sort_order');
            $table->integer('show_hide');
            $table->integer('qty');
        });
        Schema::connection('legacy')->create('igi_post_attachments', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('post_id');
            $table->string('attachment');
            $table->string('attachment_big');
            $table->integer('sort_order');
        });

        DB::connection('legacy')->table('igi_category')->insert([
            'id' => 10, 'parent_id' => 0, 'name' => 'Wallets', 'slug' => 'wallets', 'content' => '',
            'attachment' => 'wallets.jpg', 'position' => 'Menu', 'status' => 1,
        ]);
        DB::connection('legacy')->table('igi_posts')->insert([
            'id' => 100, 'category' => '10', 'title' => 'Cowhide Wallet', 'content' => '<p>Leather wallet</p>',
            'product_code' => 'W100', 'weight' => 1, 'slug' => 'cowhide-wallet',
            'main_image' => '/upload/post/w100.jpg', 'status' => 1, 'type' => 'both', 'timestamp' => '2026-01-01 10:00:00',
        ]);
        DB::connection('legacy')->table('igi_post_variation')->insert([
            'id' => 200, 'post_id' => 100, 'color' => 'Black', 'size' => '', 'price' => 20,
            'wholesale_price' => 10, 'wholesale_min_qty' => 2, 'sort_order' => 1, 'show_hide' => 1, 'qty' => 12,
        ]);
        DB::connection('legacy')->table('igi_post_attachments')->insert([
            'id' => 300, 'post_id' => 100, 'attachment' => 'thumb-w100.jpg',
            'attachment_big' => 'w100.jpg', 'sort_order' => 1,
        ]);
    }

    public function test_it_imports_the_legacy_catalogue_idempotently(): void
    {
        $this->artisan('legacy:import-catalogue')->assertSuccessful();
        $this->artisan('legacy:import-catalogue')->assertSuccessful();

        $this->assertSame(1, Category::count());
        $this->assertSame(1, Product::count());
        $this->assertSame(1, ProductVariant::count());
        $this->assertSame(1, ProductImage::count());
        $this->assertSame('10.00', ProductVariant::first()->wholesale_price);
        $this->assertTrue(ProductImage::first()->is_primary);
        $this->assertTrue(Product::first()->categories->contains(Category::first()));
    }

    public function test_dry_run_does_not_write_target_records(): void
    {
        $this->artisan('legacy:import-catalogue --dry-run')->assertSuccessful();

        $this->assertSame(0, Product::count());
    }

    public function test_duplicate_legacy_slugs_receive_stable_suffixes(): void
    {
        DB::connection('legacy')->table('igi_category')->insert([
            'id' => 11, 'parent_id' => 0, 'name' => 'Card Wallets', 'slug' => 'wallets', 'content' => '',
            'attachment' => '', 'position' => 'Menu', 'status' => 1,
        ]);
        DB::connection('legacy')->table('igi_posts')->insert([
            'id' => 101, 'category' => '11', 'title' => 'Second Wallet', 'content' => '',
            'product_code' => 'W101', 'weight' => 1, 'slug' => 'cowhide-wallet',
            'main_image' => null, 'status' => 1, 'type' => 'wholesale', 'timestamp' => '2026-01-02 10:00:00',
        ]);

        $this->artisan('legacy:import-catalogue')->assertSuccessful();

        $this->assertDatabaseHas('categories', ['legacy_id' => 11, 'slug' => 'wallets-legacy-11']);
        $this->assertDatabaseHas('products', ['legacy_id' => 101, 'slug' => 'cowhide-wallet-legacy-101']);
    }
}
