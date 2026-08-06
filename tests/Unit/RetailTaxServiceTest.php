<?php

namespace Tests\Unit;

use App\Services\RetailTaxService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RetailTaxServiceTest extends TestCase
{
    public function test_it_uses_current_destination_based_gst_and_hst_rates(): void
    {
        config()->set('retail-tax.gst_hst_registered', true);
        $taxes = app(RetailTaxService::class);

        $this->assertSame(['HST', 0.13, 13.0], $this->summary($taxes->calculate(100, 'CA', 'Ontario')));
        $this->assertSame(['HST', 0.14, 14.0], $this->summary($taxes->calculate(100, 'CA', 'NS')));
        $this->assertSame(['HST', 0.15, 15.0], $this->summary($taxes->calculate(100, 'CA', 'New Brunswick')));
        $this->assertSame(['GST', 0.05, 5.0], $this->summary($taxes->calculate(100, 'CA', 'Alberta')));
    }

    public function test_tax_collection_stays_off_until_registration_is_confirmed(): void
    {
        config()->set('retail-tax.gst_hst_registered', false);

        $result = app(RetailTaxService::class)->calculate(100, 'CA', 'Ontario');

        $this->assertFalse($result['enabled']);
        $this->assertSame(0.0, $result['rate']);
        $this->assertSame(0.0, $result['amount']);
    }

    public function test_canadian_tax_is_not_charged_for_a_us_destination(): void
    {
        config()->set('retail-tax.gst_hst_registered', true);

        $result = app(RetailTaxService::class)->calculate(100, 'US', 'New York');

        $this->assertFalse($result['enabled']);
        $this->assertSame(0.0, $result['amount']);
    }

    public function test_an_unknown_canadian_province_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(RetailTaxService::class)->calculate(100, 'CA', 'Unknown');
    }

    private function summary(array $result): array
    {
        return [$result['label'], $result['rate'], $result['amount']];
    }
}
