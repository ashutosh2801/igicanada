<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RetailAddressService
{
    public function all(Request $request): array
    {
        return array_values($request->session()->get('retail_addresses', []));
    }

    public function find(Request $request, string $id): ?array
    {
        foreach ($this->all($request) as $address) {
            if ($address['id'] === $id) {
                return $address;
            }
        }

        return null;
    }

    public function store(Request $request, array $data): array
    {
        $addresses = $this->all($request);
        $isPrimary = ! empty($data['is_primary']) || $addresses === [];

        if ($isPrimary) {
            foreach ($addresses as &$address) {
                $address['is_primary'] = false;
            }
            unset($address);
        }

        $address = [
            'id' => Str::uuid()->toString(),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'country' => $data['country'],
            'country_code' => $data['country_code'] ?? null,
            'postal_code' => $data['postal_code'],
            'is_primary' => $isPrimary,
        ];

        $addresses[] = $address;
        $request->session()->put('retail_addresses', $addresses);

        return $address;
    }

    public function update(Request $request, string $id, array $data): ?array
    {
        $addresses = $this->all($request);
        $found = null;
        foreach ($addresses as $index => $address) {
            if ($address['id'] === $id) {
                $found = $index;
                break;
            }
        }
        if ($found === null) {
            return null;
        }

        if (! empty($data['is_primary'])) {
            foreach ($addresses as $i => $address) {
                $addresses[$i]['is_primary'] = $address['id'] === $id;
            }
        }

        $addresses[$found] = array_merge($addresses[$found], [
            'name' => $data['name'],
            'email' => $data['email'] ?? $addresses[$found]['email'] ?? null,
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'country' => $data['country'],
            'country_code' => $data['country_code'] ?? $addresses[$found]['country_code'] ?? null,
            'postal_code' => $data['postal_code'],
            'is_primary' => ! empty($data['is_primary']) ? true : $addresses[$found]['is_primary'],
        ]);

        $request->session()->put('retail_addresses', $addresses);

        return $addresses[$found];
    }

    public function destroy(Request $request, string $id): void
    {
        $addresses = array_values(array_filter($this->all($request), fn ($address) => $address['id'] !== $id));
        $request->session()->put('retail_addresses', $addresses);
    }
}
