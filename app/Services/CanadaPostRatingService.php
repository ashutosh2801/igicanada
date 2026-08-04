<?php

namespace App\Services;

use App\Exceptions\CanadaPostException;
use App\Models\CartItem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Symfony\Component\Intl\Countries;
use Throwable;
use XMLWriter;

class CanadaPostRatingService
{
    private const NAMESPACE = 'http://www.canadapost.ca/ws/ship/rate-v4';

    public function configured(): bool
    {
        return (bool) config('canada-post.enabled')
            && filled(config('canada-post.api_key'))
            && filled(config('canada-post.api_secret'))
            && filled(config('canada-post.origin_postal_code'));
    }

    /**
     * @param  Collection<int, CartItem>  $items
     * @param  array{country:string, country_code?:string, postal_code:string}  $destination
     * @return array<int, array{code:string, name:string, price:string, transitDays:int|null, deliveryDate:string|null}>
     */
    public function rates(Collection $items, array $destination): array
    {
        if (! $this->configured()) {
            throw new CanadaPostException('Canada Post shipping is not configured yet.');
        }

        $packageWeights = $this->packageWeights($items);
        $quotesByPackage = collect($packageWeights)->map(
            fn (float $weight) => collect($this->requestRates($weight, $destination))->keyBy('code')
        );

        $serviceCodes = $quotesByPackage->first()?->keys() ?? collect();
        foreach ($quotesByPackage as $quotes) {
            $serviceCodes = $serviceCodes->intersect($quotes->keys());
        }

        return $serviceCodes->map(function (string $code) use ($quotesByPackage): array {
            $quotes = $quotesByPackage->map(fn (Collection $package) => $package->get($code))->filter();
            $first = $quotes->first();

            return [
                'code' => $code,
                'name' => $first['name'],
                'price' => number_format($quotes->sum(fn (array $quote) => (float) $quote['price']), 2, '.', ''),
                'transitDays' => $quotes->max('transitDays'),
                'deliveryDate' => $quotes->pluck('deliveryDate')->filter()->sort()->last(),
            ];
        })->sortBy('price', SORT_NUMERIC)->values()->all();
    }

    /** @return array<int, float> */
    private function packageWeights(Collection $items): array
    {
        $fallback = max(0.001, (float) config('canada-post.default_item_weight_kg', 0.25));
        $maximum = max(0.001, (float) config('canada-post.maximum_package_weight_kg', 30));
        $total = $items->sum(function ($item) use ($fallback): float {
            $weight = (float) ($item->variant?->product?->weight_kg ?: $fallback);

            return max(0.001, $weight) * (int) $item->quantity;
        });

        if ($total <= 0) {
            throw new CanadaPostException('The cart does not contain a shippable package.');
        }

        $packages = [];
        while ($total > 0.0001) {
            $weight = min($total, $maximum);
            $packages[] = round($weight, 3);
            $total -= $weight;
        }

        return $packages;
    }

    /**
     * @param  array{country:string, country_code?:string, postal_code:string}  $destination
     * @return array<int, array{code:string, name:string, price:string, transitDays:int|null, deliveryDate:string|null}>
     */
    private function requestRates(float $weight, array $destination): array
    {
        try {
            $response = $this->client()->withBody(
                $this->requestXml($weight, $destination),
                'application/vnd.cpc.ship.rate-v4+xml'
            )->post($this->endpoint());
        } catch (ConnectionException $exception) {
            throw new CanadaPostException('Canada Post could not be reached. Please try again.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new CanadaPostException($this->errorMessage($response->body()));
        }

        return $this->parseQuotes($response->body());
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth((string) config('canada-post.api_key'), (string) config('canada-post.api_secret'))
            ->accept('application/vnd.cpc.ship.rate-v4+xml')
            ->withHeaders(['Accept-Language' => 'en-CA'])
            ->timeout((int) config('canada-post.timeout_seconds', 12));
    }

    private function endpoint(): string
    {
        $host = config('canada-post.environment') === 'production'
            ? 'https://soa-gw.canadapost.ca'
            : 'https://ct.soa-gw.canadapost.ca';

        return $host.'/rs/ship/price';
    }

    /** @param array{country:string, country_code?:string, postal_code:string} $destination */
    private function requestXml(float $weight, array $destination): string
    {
        $country = filled($destination['country_code'] ?? null)
            ? strtoupper((string) $destination['country_code'])
            : $this->countryCode($destination['country']);
        if (! preg_match('/^[A-Z]{2}$/', $country)) {
            throw new CanadaPostException('Please select a supported destination country.');
        }
        $postalCode = strtoupper(preg_replace('/\s+/', '', $destination['postal_code']));
        if ($postalCode === '') {
            throw new CanadaPostException('A destination postal or ZIP code is required.');
        }

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'mailing-scenario', self::NAMESPACE);
        if (filled(config('canada-post.customer_number'))) {
            $xml->writeElement('customer-number', preg_replace('/\D/', '', (string) config('canada-post.customer_number')));
        }
        if (filled(config('canada-post.contract_id'))) {
            $xml->writeElement('contract-id', (string) config('canada-post.contract_id'));
        }
        $xml->startElement('parcel-characteristics');
        $xml->writeElement('weight', number_format($weight, 3, '.', ''));
        $xml->endElement();
        $xml->writeElement('origin-postal-code', strtoupper(preg_replace('/\s+/', '', (string) config('canada-post.origin_postal_code'))));
        $xml->startElement('destination');
        if ($country === 'CA') {
            $xml->startElement('domestic');
            $xml->writeElement('postal-code', $postalCode);
        } elseif ($country === 'US') {
            $xml->startElement('united-states');
            $xml->writeElement('zip-code', $postalCode);
        } else {
            $xml->startElement('international');
            $xml->writeElement('country-code', $country);
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function countryCode(string $country): string
    {
        $normalized = strtolower(trim($country));
        $aliases = [
            'canada' => 'CA',
            'ca' => 'CA',
            'united states' => 'US',
            'united states of america' => 'US',
            'usa' => 'US',
            'us' => 'US',
        ];
        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }
        if (preg_match('/^[a-z]{2}$/i', $normalized)) {
            return strtoupper($normalized);
        }

        try {
            $names = Countries::getNames('en');
            $code = array_search($country, $names, true);
        } catch (Throwable) {
            $code = false;
        }

        if (! $code) {
            throw new CanadaPostException('Please select a supported destination country.');
        }

        return $code;
    }

    /** @return array<int, array{code:string, name:string, price:string, transitDays:int|null, deliveryDate:string|null}> */
    private function parseQuotes(string $body): array
    {
        $xml = $this->xml($body);
        $xml->registerXPathNamespace('cp', self::NAMESPACE);
        $quotes = $xml->xpath('//cp:price-quote') ?: [];

        $parsed = collect($quotes)->map(function (SimpleXMLElement $quote): array {
            return [
                'code' => (string) $quote->{'service-code'},
                'name' => (string) $quote->{'service-name'},
                'price' => number_format((float) $quote->{'price-details'}->due, 2, '.', ''),
                'transitDays' => filled((string) $quote->{'service-standard'}->{'expected-transit-time'})
                    ? (int) $quote->{'service-standard'}->{'expected-transit-time'} : null,
                'deliveryDate' => filled((string) $quote->{'service-standard'}->{'expected-delivery-date'})
                    ? (string) $quote->{'service-standard'}->{'expected-delivery-date'} : null,
            ];
        })->filter(fn (array $quote) => $quote['code'] !== '')->values()->all();

        if ($parsed === []) {
            throw new CanadaPostException('Canada Post returned no shipping services for this address.');
        }

        return $parsed;
    }

    private function errorMessage(string $body): string
    {
        try {
            $xml = $this->xml($body);
            $messages = $xml->xpath('//*[local-name()="description"]');
            if ($messages && filled((string) $messages[0])) {
                return 'Canada Post: '.(string) $messages[0];
            }
        } catch (CanadaPostException) {
            // Use the stable public message below for malformed gateway errors.
        }

        return 'Canada Post could not calculate shipping for this address.';
    }

    private function xml(string $body): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($xml === false) {
            throw new CanadaPostException('Canada Post returned an invalid response.');
        }

        return $xml;
    }
}
