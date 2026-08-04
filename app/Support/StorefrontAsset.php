<?php

namespace App\Support;

class StorefrontAsset
{
    public static function uploaded(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return '/storage/'.ltrim($path, '/');
    }

    public static function legacy(?string $path, string $folder = 'post'): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $normalized = str_starts_with($path, '/') ? $path : "/upload/{$folder}/{$path}";

        return 'https://igicanada.ca'.$normalized;
    }
}
