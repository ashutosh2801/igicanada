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
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreignId('image_media_asset_id')->nullable()->after('image_path')->constrained('media_assets')->nullOnDelete();
        });

        $rows = DB::table('categories')
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->get(['id', 'image_path']);

        foreach ($rows as $row) {
            $path = (string) $row->image_path;

            if (str_starts_with($path, 'http') || str_starts_with($path, '/upload')) {
                $disk = 'legacy';
            } elseif (str_starts_with($path, '/')) {
                $disk = 'public';
                $path = ltrim($path, '/');
            } elseif (str_contains($path, '/')) {
                $disk = 'public';
            } else {
                $disk = 'legacy';
                $path = '/upload/category/'.$path;
            }

            $assetId = DB::table('media_assets')->where('path', $path)->value('id');

            if ($assetId === null) {
                $assetId = DB::table('media_assets')->insertGetId([
                    'disk' => $disk,
                    'path' => $path,
                    'filename' => basename(parse_url($path, PHP_URL_PATH) ?: $path),
                    'title' => Str::of(pathinfo(basename($path), PATHINFO_FILENAME))->replace(['-', '_'], ' ')->title()->limit(255),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('categories')->where('id', $row->id)->update(['image_media_asset_id' => $assetId]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('image_media_asset_id');
        });
    }
};