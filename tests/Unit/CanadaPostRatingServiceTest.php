<?php

namespace Tests\Unit;

use App\Services\CanadaPostRatingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CanadaPostRatingServiceTest extends TestCase
{
    public function test_it_sends_a_domestic_rating_request_and_normalizes_quotes(): void
    {
        config()->set('canada-post.enabled', true);
        config()->set('canada-post.api_key', 'api-user');
        config()->set('canada-post.api_secret', 'api-password');
        config()->set('canada-post.origin_postal_code', 'L4W 2S1');
        Http::fake(['ct.soa-gw.canadapost.ca/*' => Http::response(<<<'XML'
<price-quotes xmlns="http://www.canadapost.ca/ws/ship/rate-v4">
  <price-quote>
    <service-code>DOM.XP</service-code><service-name>Xpresspost</service-name>
    <price-details><due>18.75</due></price-details>
    <service-standard><expected-transit-time>1</expected-transit-time><expected-delivery-date>2026-08-04</expected-delivery-date></service-standard>
  </price-quote>
</price-quotes>
XML, 200)]);

        $product = (object) ['weight_kg' => 0.5];
        $variant = (object) ['product' => $product];
        $items = new Collection([(object) ['variant' => $variant, 'quantity' => 4]]);

        $rates = app(CanadaPostRatingService::class)->rates($items, [
            'country' => 'Canada',
            'postal_code' => 'M5H 1A1',
        ]);

        $this->assertSame('DOM.XP', $rates[0]['code']);
        $this->assertSame('18.75', $rates[0]['price']);
        $this->assertSame(1, $rates[0]['transitDays']);
        Http::assertSent(function ($request): bool {
            $authorization = $request->header('Authorization')[0] ?? '';

            return $request->url() === 'https://ct.soa-gw.canadapost.ca/rs/ship/price'
                && $authorization === 'Basic '.base64_encode('api-user:api-password')
                && str_contains($request->body(), '<weight>2.000</weight>')
                && str_contains($request->body(), '<origin-postal-code>L4W2S1</origin-postal-code>')
                && str_contains($request->body(), '<postal-code>M5H1A1</postal-code>');
        });
    }
}
