<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class RetailTaxService
{
    /**
     * @return array{enabled: bool, label: string, jurisdiction: string, rate: float, amount: float, registration_number: ?string}
     */
    public function calculate(float $taxableAmount, string $countryCode, string $province): array
    {
        $countryCode = strtoupper(trim($countryCode));
        if ($countryCode !== 'CA') {
            return $this->result(false, 'Canadian sales tax', $countryCode, 0, 0);
        }

        $provinceCode = $this->provinceCode($province);
        $tax = config("retail-tax.rates.{$provinceCode}");
        if (! is_array($tax)) {
            throw ValidationException::withMessages([
                'province' => 'Select a valid Canadian province or territory for tax calculation.',
            ]);
        }

        $registered = (bool) config('retail-tax.gst_hst_registered');

        // Leatherwallets charges a flat 13% HST on all Canadian orders,
        // regardless of the destination province's GST/HST rate.
        $rate = $registered ? 0.13 : 0.0;

        return $this->result(
            $registered,
            'HST',
            $provinceCode,
            $rate,
            round(max(0, $taxableAmount) * $rate, 2),
        );
    }

    private function provinceCode(string $province): string
    {
        $value = strtoupper(trim($province));
        $provinces = [
            'ALBERTA' => 'AB',
            'BRITISH COLUMBIA' => 'BC',
            'MANITOBA' => 'MB',
            'NEW BRUNSWICK' => 'NB',
            'NEWFOUNDLAND AND LABRADOR' => 'NL',
            'NEWFOUNDLAND & LABRADOR' => 'NL',
            'NOVA SCOTIA' => 'NS',
            'NORTHWEST TERRITORIES' => 'NT',
            'NUNAVUT' => 'NU',
            'ONTARIO' => 'ON',
            'PRINCE EDWARD ISLAND' => 'PE',
            'QUEBEC' => 'QC',
            'QUÉBEC' => 'QC',
            'SASKATCHEWAN' => 'SK',
            'YUKON' => 'YT',
        ];

        return $provinces[$value] ?? $value;
    }

    private function result(bool $enabled, string $label, string $jurisdiction, float $rate, float $amount): array
    {
        return [
            'enabled' => $enabled,
            'label' => $label,
            'jurisdiction' => $jurisdiction,
            'rate' => $rate,
            'amount' => $amount,
            'registration_number' => config('retail-tax.registration_number'),
        ];
    }
}
