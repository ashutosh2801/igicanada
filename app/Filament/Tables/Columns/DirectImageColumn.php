<?php

namespace App\Filament\Tables\Columns;

use App\Support\StorefrontAsset;
use Filament\Tables\Columns\ImageColumn;

class DirectImageColumn extends ImageColumn
{
    public function getImageUrl(?string $state = null): ?string
    {
        if ($directUrl = StorefrontAsset::directUrl($state)) {
            return $directUrl;
        }

        // Product and media models already return browser paths such as
        // /storage/... or /upload/.... Do not pass them back through
        // Filament's filesystem resolver, which can prepend /storage again
        // and generate URLs like /storage//storage/....
        if (is_string($state) && str_starts_with($state, '/')) {
            return preg_replace('#^/storage//+#', '/storage/', $state)
                ?: $state;
        }

        return parent::getImageUrl($state);
    }
}
