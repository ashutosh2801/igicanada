<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Services\RetailAddressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request, RetailAddressService $sessionAddresses): Response
    {
        $user = $request->user();

        $addresses = $user
            ? $this->presentDb($user->addresses()->orderByRaw("case when type = 'primary' then 0 else 1 end")->orderByDesc('id')->get())
            : $sessionAddresses->all($request);

        return Inertia::render('Account/Addresses', [
            'addresses' => $addresses,
            'guest' => $user === null,
        ]);
    }

    public function store(Request $request, RetailAddressService $sessionAddresses): RedirectResponse
    {
        $data = $this->validated($request);
        $user = $request->user();

        if ($user) {
            $makePrimary = ! empty($data['is_primary']) || $user->addresses()->doesntExist();
            if ($makePrimary) {
                $user->addresses()->where('type', 'primary')->update(['type' => 'shipping']);
            }
            $user->addresses()->create([
                'type' => $makePrimary ? 'primary' : 'shipping',
                'address_line1' => $data['address'],
                'city' => $data['city'],
                'province' => $data['province'],
                'country' => $data['country'],
                'postal_code' => $data['postal_code'],
                'telephone' => $data['phone'],
            ]);
        } else {
            $sessionAddresses->store($request, $data);
        }

        return redirect()->route('retail.addresses.index')->with('status', 'Address saved.');
    }

    public function update(Request $request, RetailAddressService $sessionAddresses, string $id): RedirectResponse
    {
        $data = $this->validated($request);
        $user = $request->user();

        if ($user) {
            $address = $user->addresses()->whereKey((int) $id)->first();
            if (! $address) {
                throw ValidationException::withMessages(['address' => 'Address not found.']);
            }
            if (! empty($data['is_primary'])) {
                $user->addresses()->whereKeyNot($address->id)->where('type', 'primary')->update(['type' => 'shipping']);
                $address->type = 'primary';
            } else {
                $address->type = 'shipping';
            }
            $address->update([
                'address_line1' => $data['address'],
                'city' => $data['city'],
                'province' => $data['province'],
                'country' => $data['country'],
                'postal_code' => $data['postal_code'],
                'telephone' => $data['phone'],
            ]);
        } else {
            $updated = $sessionAddresses->update($request, $id, $data);
            if ($updated === null) {
                throw ValidationException::withMessages(['address' => 'Address not found.']);
            }
        }

        return redirect()->route('retail.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(Request $request, RetailAddressService $sessionAddresses, string $id): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $address = $user->addresses()->whereKey((int) $id)->first();
            if ($address) {
                $address->delete();
            }
        } else {
            $sessionAddresses->destroy($request, $id);
        }

        return redirect()->route('retail.addresses.index')->with('status', 'Address removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:300'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'postal_code' => ['required', 'string', 'max:20'],
            'is_primary' => ['nullable', 'boolean'],
        ]);
    }

    protected function presentDb($addresses): array
    {
        return $addresses->map(fn (CustomerAddress $address) => [
            'id' => (string) $address->id,
            'name' => '',
            'email' => null,
            'phone' => $address->telephone ?: $address->mobile,
            'address' => $address->address_line1,
            'city' => $address->city,
            'province' => $address->province,
            'country' => $address->country,
            'country_code' => null,
            'postal_code' => $address->postal_code,
            'is_primary' => $address->type === 'primary',
        ])->values()->all();
    }
}
