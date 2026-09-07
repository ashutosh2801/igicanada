<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    public $timestamps = false;

    protected $table = 'igi_email_template';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function scopeActive($query): Builder
    {
        return $query->where('status', true);
    }

    /** @return array<string, string> */
    public static function channelOptions(): array
    {
        return [
            'wholesale' => 'IGI Canada',
            'retail' => 'Leather Wallets',
        ];
    }

    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('sales_channel', $channel);
    }

    public static function byName(string $name, string $channel = 'wholesale'): ?self
    {
        return static::active()->where('name', $name)->where('sales_channel', $channel)->first();
    }

    /** @param array<string, string> $data */
    public function render(array $data = []): string
    {
        $content = $this->content;
        $content = preg_replace('/\{\{\s*/', '{{', $content);
        $content = preg_replace('/\s*\}\}/', '}}', $content);

        foreach ($data as $key => $value) {
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
        }

        return (string) preg_replace('/\{\{[^}]+\}\}/', '', $content);
    }

    /** @param array<string, string> $data */
    public function renderSubject(array $data = []): string
    {
        $subject = $this->subject;

        foreach ($data as $key => $value) {
            $subject = str_replace('{{'.$key.'}}', (string) $value, $subject);
        }

        return (string) preg_replace('/\{\{[^}]+\}\}/', '', $subject);
    }
}
