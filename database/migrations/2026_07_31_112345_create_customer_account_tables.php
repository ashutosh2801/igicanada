<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('name');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            $table->string('legacy_username')->nullable()->after('name');
            $table->string('legacy_email')->nullable()->after('email');
            $table->string('account_type')->default('customer')->index()->after('password');
            $table->string('approval_status')->default('pending')->index()->after('account_type');
            $table->unsignedTinyInteger('legacy_role_id')->nullable()->after('approval_status');
            $table->foreignId('price_tier_id')->nullable()->after('legacy_role_id')->constrained()->nullOnDelete();
            $table->boolean('must_reset_password')->default(false)->after('price_tier_id');
            $table->timestamp('approved_at')->nullable()->after('must_reset_password');
        });

        Schema::create('reseller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company')->nullable();
            $table->string('alternate_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('business_number')->nullable();
            $table->string('area')->nullable();
            $table->unsignedBigInteger('legacy_country_id')->nullable();
            $table->string('legacy_province_value')->nullable();
            $table->string('profile_photo')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('type')->default('shipping');
            $table->text('address_line1')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('telephone')->nullable();
            $table->string('mobile')->nullable();
            $table->unsignedBigInteger('legacy_country_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('reseller_profiles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('price_tier_id');
            $table->dropColumn([
                'legacy_id',
                'legacy_username',
                'legacy_email',
                'account_type',
                'approval_status',
                'legacy_role_id',
                'must_reset_password',
                'approved_at',
            ]);
        });

        Schema::dropIfExists('price_tiers');
    }
};
