<?php

namespace App\Support;

use Illuminate\Http\Request;

final readonly class StorefrontContext
{
    public function __construct(
        public string $channel,
        public string $domain,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $host = strtolower($request->getHost());
        $retailDomains = [
            config('storefronts.retail.domain'),
            ...config('storefronts.retail.aliases', []),
        ];

        $channel = in_array($host, array_filter($retailDomains), true)
            ? 'retail'
            : config('storefronts.default_channel', 'wholesale');

        return new self($channel, $host);
    }

    public function isRetail(): bool
    {
        return $this->channel === 'retail';
    }

    public function isWholesale(): bool
    {
        return $this->channel === 'wholesale';
    }
}
