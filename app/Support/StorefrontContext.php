<?php

namespace App\Support;

use Illuminate\Http\Request;

final readonly class StorefrontContext
{
    public function __construct(
        public string $channel,
        public string $domain,
    ) {}

    /**
     * Domains that serve the retail storefront directly (no redirect):
     * the canonical domain plus any sibling domains.
     */
    public static function retailServedDomains(): array
    {
        return array_filter([
            config('storefronts.retail.domain'),
            ...config('storefronts.retail.siblings', []),
        ]);
    }

    public static function fromRequest(Request $request): self
    {
        $host = strtolower($request->getHost());
        $retailDomains = [
            ...self::retailServedDomains(),
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

    /**
     * The channel whose homepage/branding settings apply to this request.
     *
     * The storefront channel stays 'retail' on every retail-served domain so
     * catalogue, cart and checkout behaviour is shared, but branded sibling
     * domains (e.g. walletsandbelts.com) resolve to their own settings channel.
     */
    public function settingsChannel(): string
    {
        if ($this->channel !== 'retail') {
            return $this->channel;
        }

        return config('storefronts.retail.branded_domains', [])[$this->domain]
            ?? 'retail';
    }
}
