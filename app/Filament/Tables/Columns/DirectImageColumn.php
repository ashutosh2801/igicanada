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

        return parent::getImageUrl($state);
    }
}
