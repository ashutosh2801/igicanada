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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('sku')->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->string('primary_image_path')->nullable();
            $table->enum('visibility', ['retail', 'wholesale', 'both'])->default('wholesale');
            $table->decimal('weight_kg', 10, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->decimal('retail_price', 12, 2)->nullable();
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->unsignedInteger('wholesale_minimum_quantity')->default(1);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
