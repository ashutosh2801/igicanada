<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaAsset extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (MediaAsset $asset): void {
            $asset->filename = $asset->filename ?: basename($asset->path);
            $asset->title = $asset->title ?: Str::of(pathinfo($asset->filename, PATHINFO_FILENAME))
                ->replace(['-', '_'], ' ')
                ->title()
                ->limit(255);

            if ($asset->disk !== 'legacy' && Storage::disk($asset->disk)->exists($asset->path)) {
                $asset->mime_type = Storage::disk($asset->disk)->mimeType($asset->path) ?: null;
                $asset->size_bytes = Storage::disk($asset->disk)->size($asset->path);

                $dimensions = @getimagesize(Storage::disk($asset->disk)->path($asset->path));
                $asset->width = $dimensions[0] ?? null;
                $asset->height = $dimensions[1] ?? null;
            }
        });
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'media_folder_id');
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'primary_media_asset_id');
    }

    public function url(): string
    {
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }

        if ($this->disk === 'legacy' || str_starts_with($this->path, '/upload')) {
            return 'https://igicanada.ca/'.ltrim($this->path, '/');
        }

        // Public media is served by this application. Keeping the URL relative
        // makes previews work when the admin is opened on a different host or
        // port than APP_URL (for example localhost:8001 during development).
        if ($this->disk === 'public') {
            return '/storage/'.ltrim($this->path, '/');
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function getPreviewUrlAttribute(): string
    {
        return $this->url();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->title ?: $this->filename;
    }
}
