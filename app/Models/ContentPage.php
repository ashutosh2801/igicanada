<?php

namespace App\Models;

use App\Support\LegacyHtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ContentPage extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_legal' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected function bodyHtml(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => app(LegacyHtmlSanitizer::class)->sanitize($value),
        );
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('sales_channel', $channel);
    }
}
