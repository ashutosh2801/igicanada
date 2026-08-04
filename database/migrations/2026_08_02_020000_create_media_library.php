<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_folder_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('path')->unique();
            $table->string('filename');
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('primary_media_asset_id')->nullable()->after('primary_image_path')->constrained('media_assets')->nullOnDelete();
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->string('path')->nullable()->change();
            $table->foreignId('media_asset_id')->nullable()->after('product_variant_id')->constrained('media_assets')->cascadeOnDelete();
        });

        $paths = DB::table('products')->whereNotNull('primary_image_path')->pluck('primary_image_path')
            ->merge(DB::table('product_images')->whereNotNull('path')->pluck('path'))
            ->filter()
            ->unique()
            ->values();

        foreach ($paths as $path) {
            $path = (string) $path;
            $filename = basename(parse_url($path, PHP_URL_PATH) ?: $path);
            $assetId = DB::table('media_assets')->insertGetId([
                'disk' => str_starts_with($path, 'http') || str_starts_with($path, '/upload') ? 'legacy' : 'public',
                'path' => $path,
                'filename' => $filename,
                'title' => Str::of(pathinfo($filename, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->title()->limit(255),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('primary_image_path', $path)->update(['primary_media_asset_id' => $assetId]);
            DB::table('product_images')->where('path', $path)->update(['media_asset_id' => $assetId]);
        }
    }

    public function down(): void
    {
        DB::table('product_images')
            ->whereNull('path')
            ->orderBy('id')
            ->eachById(function (object $image): void {
                $path = DB::table('media_assets')->where('id', $image->media_asset_id)->value('path');
                DB::table('product_images')->where('id', $image->id)->update(['path' => $path ?: '']);
            });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_asset_id');
            $table->string('path')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_media_asset_id');
        });

        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('media_folders');
    }
};
