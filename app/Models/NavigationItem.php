<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opens_new_tab' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
