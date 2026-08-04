<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'featured_category_ids' => 'array',
            'hero_image_paths' => 'array',
            'show_category_menu' => 'boolean',
            'show_new_arrivals' => 'boolean',
        ];
    }
}
