<?php

namespace Tests\Feature;

use App\Models\CustomerAddress;
use App\Models\PriceTier;
use App\Models\ResellerProfile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportLegacyAccountsTest extends TestCase
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

        Schema::connection('legacy')->create('igi_users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->string('token');
            $table->integer('role');
            $table->integer('status');
            $table->string('created');
            $table->integer('discount_level');
        });
        Schema::connection('legacy')->create('igi_user_details', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('user_id');
            $table->string('name');
            $table->string('alternet_email')->nullable();
            $table->string('address')->nullable();
            $table->string('area')->nullable();
            $table->string('city')->nullable();
            $table->string('province');
            $table->integer('country');
            $table->string('zipcode')->nullable();
            $table->string('telephone')->nullable();
            $table->string('fax_no')->nullable();
            $table->string('company')->nullable();
            $table->string('hst_no')->nullable();
            $table->string('business_no')->nullable();
            $table->string('mobile')->nullable();
            $table->string('profile_photo')->nullable();
        });
        Schema::connection('legacy')->create('igi_user_address', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('user_id');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province');
            $table->integer('country');
            $table->string('zipcode')->nullable();
            $table->string('telephone')->nullable();
            $table->string('mobile')->nullable();
        });
        Schema::connection('legacy')->create('igi_wholesale_discount', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
            $table->integer('discount');
        });
        Schema::connection('legacy')->create('igi_locations', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        DB::connection('legacy')->table('igi_locations')->insert([
            ['id' => 2, 'name' => 'Canada'],
            ['id' => 7, 'name' => 'Ontario'],
        ]);
        DB::connection('legacy')->table('igi_wholesale_discount')->insert([
            ['id' => 1, 'name' => 'Level 1', 'discount' => 0],
            ['id' => 2, 'name' => 'Level 2', 'discount' => 20],
        ]);
        DB::connection('legacy')->table('igi_users')->insert([
            'id' => 500,
            'username' => 'Buyer@Example.com',
            'email' => 'Buyer@Example.com',
            'password' => md5('telephone-password'),
            'token' => 'legacy-token',
            'role' => 5,
            'status' => 1,
            'created' => '2020-05-01 09:30:00',
            'discount_level' => 2,
        ]);
        DB::connection('legacy')->table('igi_user_details')->insert([
            'id' => 600,
            'user_id' => 500,
            'name' => 'Legacy Buyer',
            'alternet_email' => 'accounts@example.com',
            'address' => '10 King Street',
            'area' => 'Unit 5',
            'city' => 'Toronto',
            'province' => '7',
            'country' => 2,
            'zipcode' => 'M5H 1A1',
            'telephone' => '416-555-0100',
            'fax_no' => null,
            'company' => 'Example Leather Inc.',
            'hst_no' => 'HST123',
            'business_no' => 'BN456',
            'mobile' => null,
            'profile_photo' => null,
        ]);
        DB::connection('legacy')->table('igi_user_address')->insert([
            'id' => 700,
            'user_id' => 500,
            'address' => '20 Queen Street',
            'city' => 'Toronto',
            'province' => '7',
            'country' => 2,
            'zipcode' => 'M5H 2N2',
            'telephone' => null,
            'mobile' => null,
        ]);
    }

    public function test_it_imports_accounts_without_legacy_credentials_and_is_idempotent(): void
    {
        $legacyPassword = md5('telephone-password');

        $this->artisan('legacy:import-accounts')->assertSuccessful();
        $firstPassword = User::where('legacy_id', 500)->value('password');
        $this->artisan('legacy:import-accounts')->assertSuccessful();

        $user = User::where('legacy_id', 500)->firstOrFail();
        $this->assertSame(1, User::whereNotNull('legacy_id')->count());
        $this->assertSame('buyer@example.com', $user->email);
        $this->assertSame('wholesale', $user->account_type);
        $this->assertSame('approved', $user->approval_status);
        $this->assertTrue($user->must_reset_password);
        $this->assertTrue($user->isApprovedWholesale());
        $this->assertNotSame($legacyPassword, $user->password);
        $this->assertSame($firstPassword, $user->password);
        $this->assertSame('20.00', $user->priceTier->discount_percentage);
        $this->assertSame('Example Leather Inc.', $user->resellerProfile->company);
        $this->assertSame(1, ResellerProfile::count());
        $this->assertSame(2, CustomerAddress::count());
        $this->assertDatabaseHas('customer_addresses', [
            'legacy_id' => 700,
            'province' => 'Ontario',
            'country' => 'Canada',
        ]);
    }

    public function test_dry_run_does_not_write_accounts(): void
    {
        $this->artisan('legacy:import-accounts --dry-run')->assertSuccessful();

        $this->assertSame(0, User::count());
        $this->assertSame(0, PriceTier::count());
    }

    public function test_default_import_excludes_retail_customers(): void
    {
        DB::connection('legacy')->table('igi_users')->insert([
            'id' => 501,
            'username' => 'retail@example.com',
            'email' => 'retail@example.com',
            'password' => '',
            'token' => '',
            'role' => 4,
            'status' => 1,
            'created' => '2020-05-01 09:30:00',
            'discount_level' => 1,
        ]);

        $this->artisan('legacy:import-accounts')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['legacy_id' => 501]);
        $this->assertDatabaseHas('users', ['legacy_id' => 500]);
    }

    public function test_optional_retail_archives_are_always_suspended(): void
    {
        DB::connection('legacy')->table('igi_users')->insert([
            'id' => 501,
            'username' => 'retail@example.com',
            'email' => 'retail@example.com',
            'password' => '',
            'token' => '',
            'role' => 4,
            'status' => 1,
            'created' => '2020-05-01 09:30:00',
            'discount_level' => 1,
        ]);

        $this->artisan('legacy:import-accounts')->assertSuccessful();

        $this->assertDatabaseMissing('users', [
            'legacy_id' => 501,
            'account_type' => 'customer',
            'approval_status' => 'suspended',
        ]);
    }
}
