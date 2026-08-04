<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportLegacyOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.legacy', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge('legacy');

        Schema::connection('legacy')->create('igi_order_place', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('user_id');
            $table->integer('shipping_id');
            $table->text('order_ids')->nullable();
            $table->integer('order_no');
            $table->decimal('price');
            $table->decimal('product_price');
            $table->decimal('discount');
            $table->decimal('shipping_price');
            $table->decimal('tax');
            $table->string('currency')->nullable();
            $table->string('shipping')->nullable();
            $table->string('service')->nullable();
            $table->string('token')->nullable();
            $table->integer('status');
            $table->string('type');
            $table->string('track_no')->nullable();
            $table->dateTime('ondate');
        });
        Schema::connection('legacy')->create('igi_orders', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('user_id');
            $table->integer('product_id');
            $table->integer('varaition_id');
            $table->string('size')->nullable();
            $table->integer('qty');
            $table->decimal('price');
            $table->dateTime('createdon');
        });
        Schema::connection('legacy')->create('igi_posts', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('title');
            $table->string('product_code')->nullable();
        });
        Schema::connection('legacy')->create('igi_post_variation', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
        });
        Schema::connection('legacy')->create('igi_user_address', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('telephone')->nullable();
        });

        DB::connection('legacy')->table('igi_posts')->insert(['id' => 100, 'title' => 'Legacy Wallet', 'product_code' => 'W100']);
        DB::connection('legacy')->table('igi_post_variation')->insert(['id' => 200, 'color' => 'Black', 'size' => null]);
        DB::connection('legacy')->table('igi_orders')->insert([
            'id' => 1000, 'user_id' => 500, 'product_id' => 100, 'varaition_id' => 200,
            'size' => null, 'qty' => 3, 'price' => 8, 'createdon' => '2024-01-01 10:00:00',
        ]);
        DB::connection('legacy')->table('igi_user_address')->insert([
            'id' => 700, 'address' => '10 King Street', 'city' => 'Toronto',
            'province' => 'Ontario', 'zipcode' => 'M5H 1A1', 'telephone' => '4165550100',
        ]);
        foreach ([[1, 'unpaid'], [2, 'paid']] as [$id, $type]) {
            DB::connection('legacy')->table('igi_order_place')->insert([
                'id' => $id, 'user_id' => 500, 'shipping_id' => 700, 'order_ids' => '1000',
                'order_no' => 202401011000, 'price' => 24, 'product_price' => 24,
                'discount' => 0, 'shipping_price' => 0, 'tax' => 0, 'currency' => 'CAD',
                'shipping' => 'standard', 'service' => null, 'token' => $type === 'paid' ? 'EC-PAID' : 'attempt',
                'status' => $type === 'paid' ? 2 : 0, 'type' => $type, 'track_no' => null,
                'ondate' => '2024-01-01 10:05:00',
            ]);
        }

        $user = User::factory()->create(['legacy_id' => 500]);
        $user->addresses()->create(['legacy_id' => 700, 'type' => 'shipping', 'address_line1' => '10 King Street']);
        $product = Product::create([
            'legacy_id' => 100, 'name' => 'Legacy Wallet', 'slug' => 'legacy-wallet',
            'visibility' => 'wholesale', 'is_active' => true,
        ]);
        $product->variants()->create([
            'legacy_id' => 200, 'wholesale_price' => 8, 'stock_quantity' => 10,
            'wholesale_minimum_quantity' => 1, 'is_active' => true,
        ]);
    }

    public function test_it_preserves_duplicate_payment_attempts_and_is_idempotent(): void
    {
        $this->artisan('legacy:import-orders')->assertSuccessful();
        $this->artisan('legacy:import-orders')->assertSuccessful();

        $this->assertSame(2, Order::whereNotNull('legacy_id')->count());
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseHas('orders', ['legacy_id' => 1, 'payment_status' => 'unpaid']);
        $this->assertDatabaseHas('orders', ['legacy_id' => 2, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('orders', ['legacy_id' => 2, 'order_number' => 'LEGACY-202401011000-2']);
        $this->assertDatabaseHas('order_items', ['legacy_id' => 1000, 'product_name' => 'Legacy Wallet', 'line_total' => 24]);
    }

    public function test_dry_run_does_not_write_orders(): void
    {
        $this->artisan('legacy:import-orders --dry-run')->assertSuccessful();

        $this->assertSame(0, Order::count());
    }

    public function test_deleted_legacy_customer_is_preserved_as_suspended_archive_account(): void
    {
        DB::connection('legacy')->table('igi_order_place')->insert([
            'id' => 3, 'user_id' => 999, 'shipping_id' => 700, 'order_ids' => '1000',
            'order_no' => 202401011100, 'price' => 24, 'product_price' => 24,
            'discount' => 0, 'shipping_price' => 0, 'tax' => 0, 'currency' => '',
            'shipping' => null, 'service' => null, 'token' => null, 'status' => 4,
            'type' => 'paid', 'track_no' => 'TRACK-1', 'ondate' => '2024-01-01 11:00:00',
        ]);

        $this->artisan('legacy:import-orders')->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'legacy_id' => 999,
            'email' => 'archived-999@invalid.igicanada.local',
            'approval_status' => 'suspended',
        ]);
        $this->assertDatabaseHas('orders', [
            'legacy_id' => 3,
            'status' => 'completed',
            'currency' => 'CAD',
        ]);
    }
}
